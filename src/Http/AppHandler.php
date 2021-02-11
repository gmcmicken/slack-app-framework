<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Auth\SingleTokenStore;
use Jeremeamia\Slack\Apps\Auth\TokenStore;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function array_filter;
use function strpos;

class AppHandler implements HandlerInterface
{
    /** @var TokenStore */
    private $tokenStore;

    /** @var AsyncProcessor|null */
    private $asyncProcessor;

    /** @var Listener */
    private $listener;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Listener $listener
     * @param TokenStore|null $tokenStore
     * @param AsyncProcessor|null $asyncProcessor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Listener $listener,
        ?TokenStore $tokenStore = null,
        ?AsyncProcessor $asyncProcessor = null,
        ?LoggerInterface $logger = null
    ) {
        $this->listener = $listener;
        $this->tokenStore = $tokenStore ?? new SingleTokenStore();
        $this->asyncProcessor = $asyncProcessor ?? new PreAckProcessor($listener);
        $this->logger = $logger ?? new NullLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Prepare the app context for the listener(s).
        $ackClient = new HttpServerAckClient();
        $context = $this->getContext($request)
            ->withAckClient($ackClient)
            ->withTokenStore($this->tokenStore);

        // Delegate to the listener(s) for handling the app context.
        $this->listener->handle($context);
        if ($context->isDeferred()) {
            $this->asyncProcessor->process($context);
        }

        // Return an "ack" response for Slack.
        return $ackClient->getAckResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * @return Context
     * @throws HttpException if payload cannot be parsed for context.
     */
    private function getContext(ServerRequestInterface $request): Context
    {
        $context = new Context(Util::parsePayloadFromRequest($request), [
            'http' => [
                'headers' => array_filter($request->getHeaders(), function (string $key) {
                    return strpos($key, 'X-Slack') === 0;
                }, ARRAY_FILTER_USE_KEY),
                'query' => $request->getQueryParams(),
            ]
        ]);

        $context->withLogger($this->logger);
        $this->logger->debug('Incoming Slack request', $context->toArray());

        return $context;
    }
}
