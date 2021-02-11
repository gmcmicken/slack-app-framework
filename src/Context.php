<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use ArrayAccess;
use Jeremeamia\Slack\Apps\Auth\SingleTokenStore;
use Jeremeamia\Slack\Apps\Auth\TokenStore;
use Jeremeamia\Slack\Apps\Contexts\AckClient;
use Jeremeamia\Slack\Apps\Contexts\DataBag;
use Jeremeamia\Slack\Apps\Contexts\PsrResponseClient;
use Jeremeamia\Slack\Apps\Contexts\ResponseClient;
use Jeremeamia\Slack\Apps\Contexts\Blocks;
use Jeremeamia\Slack\Apps\Contexts\ClassContainer;
use Jeremeamia\Slack\Apps\Contexts\Modals;
use Jeremeamia\Slack\Apps\Contexts\Payload;
use Jeremeamia\Slack\Apps\Contexts\PayloadType;
use Jeremeamia\Slack\Apps\Contexts\View;
use Jeremeamia\Slack\BlockKit\Formatter;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Partials\OptionList;
use Jeremeamia\Slack\BlockKit\Surfaces\AppHome;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use JoliCode\Slack\Api\Client as ApiClient;
use JoliCode\Slack\ClientFactory as ApiClientFactory;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClientFactory;
use Symfony\Component\HttpClient\Psr18Client as SymfonyPsr18Client;
use Throwable;

/**
 * A Slack "context" provides an interface to all the data and affordances for an incoming Slack request.
 */
class Context implements ArrayAccess
{
    use Contexts\HasData;

    private const ACKNOWLEDGED_KEY = '_acknowledged';
    private const DEFERRED_KEY = '_deferred';
    private const PAYLOAD_KEY = '_payload';

    /** @var AckClient|null */
    private $ackClient;

    /** @var ApiClient|null */
    private $apiClient;

    /** @var Blocks|null */
    private $blocks;

    /** @var ContainerInterface|null */
    protected $container;

    /** @var HttpClient|null */
    private $httpClient;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var Payload */
    private $payload;

    /** @var ResponseClient|null */
    private $responseClient;

    /** @var TokenStore|null */
    private $tokenStore;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $payloadData = $data[self::PAYLOAD_KEY] ?? [];
        unset($data[self::PAYLOAD_KEY]);

        return new self(new Payload($payloadData), $data);
    }

    /**
     * @param Payload $payload
     * @param array $initialData
     */
    public function __construct(Payload $payload, array $initialData = [])
    {
        $this->payload = $payload;
        $this->setData($initialData);
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface $container
     * @return static
     */
    public function withContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     * @return static
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withTokenStore(TokenStore $tokenStore): self
    {
        $this->tokenStore = $tokenStore;

        return $this;
    }

    public function withHttpClient(HttpClient $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function withAckClient(AckClient $ackClient): self
    {
        $this->ackClient = $ackClient;

        return $this;
    }

    public function withResponseClient(ResponseClient $responseClient): self
    {
        $this->responseClient = $responseClient;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        if ($key === self::PAYLOAD_KEY || $key === self::ACKNOWLEDGED_KEY || $key === self::DEFERRED_KEY) {
            throw new Exception('Cannot directly modify payload, acknowledged, or deferred state');
        }

        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function isAcknowledged(): bool
    {
        return $this[self::ACKNOWLEDGED_KEY] ?? false;
    }

    public function isDeferred(): bool
    {
        return $this[self::DEFERRED_KEY] ?? false;
    }

    public function container(): ContainerInterface
    {
        if (!$this->container) {
            $this->container = new ClassContainer();
        }

        return $this->container;
    }

    public function payload(): Payload
    {
        return $this->payload;
    }

    public function logger(): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    public function api(): ApiClient
    {
        if (!$this->apiClient) {
            $apiToken = $this->getTokenStore()->get(
                $this->payload->getTeamId(),
                $this->payload->getEnterpriseId()
            );
            $this->apiClient = ApiClientFactory::create($apiToken, $this->getHttpClient());
        }

        return $this->apiClient;
    }

    public function blocks(): Blocks
    {
        if (!$this->blocks) {
            $this->blocks = new Blocks();
        }

        return $this->blocks;
    }

    public function fmt(): Formatter
    {
        return Kit::formatter();
    }

    /**
     * Sends a 200 response as an ack back to Slack, so Slack knows the payload was received.
     *
     * Acks generally have an empty body, but for some payload types, it may be appropriate to send a message (command)
     * or other data (block_suggestion) as part of the ack.
     *
     * @param Message|JsonSerializable|array|string|null $ack Message or data to include in the ack's body.
     */
    public function ack($ack = null): void
    {
        if ($this->isAcknowledged()) {
            throw new Exception('Payload has already been acknowledged');
        }

        // Mark this context as acknowledge
        $this->data[self::ACKNOWLEDGED_KEY] = true;

        // Convert arrays to a JsonSerializable object.
        if (is_array($ack)) {
            $ack = new DataBag($ack);
        }

        // Attempt to convert everything that's not JsonSerializable to a Message.
        if ($ack !== null && !($ack instanceof JsonSerializable)) {
            $ack = Coerce::message($ack);
        }

        $this->getAckClient()->ack($ack);
    }

    /**
     * Marks the context as deferred, meaning that more processing is needed after the ack.
     *
     * This is typically not called from users' listeners, and only is significant when an asynchronous process is
     * configured to handle deferred contexts. Asynchronous handling requires advanced configurations of PHP or
     * additional infrastructure, and is not supported by any default installations of the framework or PHP. By default,
     * handling deferred contexts happens immediately before the initial ack response, so all context handling should
     * take less than 3 seconds.
     *
     * @param bool $defer
     */
    public function defer(bool $defer = true): void
    {
        $this[self::DEFERRED_KEY] = $defer;
    }

    public function error(string $error): void
    {
        throw new Exception($error);
    }

    /**
     * @param Message|array|string $message
     * @param string|null $url
     */
    public function respond($message, ?string $url = null): void
    {
        if (!$url) {
            $url = (string) $this->payload->getOneOf(['response_url', 'response_urls.0.response_url'], true);
        }

        $this->getResponseClient()->respond($url, Coerce::message($message));
    }

    /**
     * @param Message|array|string $message
     * @param string|null $channel
     * @param string|null $threadTs
     */
    public function say($message, ?string $channel = null, ?string $threadTs = null): void
    {
        try {
            $data = Coerce::message($message)->toArray();
            $this->api()->chatPostMessage(array_filter([
                'channel' => $channel ?? $this->payload->getChannelId(),
                'blocks' => isset($data['blocks']) ? json_encode($data['blocks']) : null,
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'text' => $data['text'] ?? null,
                'thread_ts' => $threadTs
            ]));
        } catch (Throwable $ex) {
            throw new Exception('API call to `chat.postMessage` failed', 0, $ex);
        }
    }

    /**
     * @param AppHome|array|string $appHome
     * @return array
     */
    public function home($appHome): array
    {
        try {
            $result = $this->api()->viewsPublish(array_filter([
                'user_id' => $this->payload->getUserId(),
                'view' =>  Coerce::appHome($appHome)->toJson(),
                'hash' => $this->payload->get('view.hash'),
            ]));

            if (!$result->getOk()) {
                throw new RuntimeException('Result is not OK');
            }

            return $result['view'] ?? [];
        } catch (Throwable $ex) {
            throw new Exception('API call to `views.publish` failed', 0, $ex);
        }
    }

    /**
     * @return Modals
     */
    public function modals(): Modals
    {
        return new Modals($this);
    }

    public function view(): View
    {
        if (!$this->payload->isType(PayloadType::viewSubmission())) {
            throw new Exception('Can only to use `view()` (response actions) for view_submission requests');
        }

        return new View($this);
    }

    /**
     * @param OptionList|array|null $options
     */
    public function options($options): void
    {
        if (!$this->payload->isType(PayloadType::blockSuggestion())) {
            throw new Exception('Can only to use `options()` for block_suggestion requests');
        }

        if (is_array($options)) {
            $options = OptionList::new()->options($options);
        }

        $this->ack($options);
    }

    public function toArray(): array
    {
        return $this->data + [self::PAYLOAD_KEY => $this->payload->toArray()];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    private function getAckClient(): AckClient
    {
        if (!$this->ackClient) {
            $this->ackClient = new class() implements AckClient {
                public function ack($message = null, bool $done = false): void
                {
                    // Do nothing.
                }
            };
        }

        return $this->ackClient;
    }

    private function getResponseClient(): ResponseClient
    {
        if (!$this->responseClient) {
            $this->responseClient = new PsrResponseClient($this->getHttpClient());
        }

        return $this->responseClient;
    }

    private function getHttpClient(): HttpClient
    {
        if (!$this->httpClient) {
            $symfonyClient = SymfonyHttpClientFactory::create();
            $this->httpClient = new SymfonyPsr18Client($symfonyClient);
        }

        return $this->httpClient;
    }

    private function getTokenStore(): TokenStore
    {
        if (!$this->tokenStore) {
            $this->tokenStore = new SingleTokenStore();
        }

        return $this->tokenStore;
    }
}
