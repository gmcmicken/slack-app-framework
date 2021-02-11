<?php

namespace Jeremeamia\Slack\Apps\Tests\Integration;

use Jeremeamia\Slack\Apps\Http\ResponseEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class IntegTestCase extends TestCase
{
    private const SIGNING_KEY = 'abc123';
    private const BOT_TOKEN = 'xoxb-abc123';
    private const HEADER_SIGNATURE = 'X-Slack-Signature';
    private const HEADER_TIMESTAMP = 'X-Slack-Request-Timestamp';

    /** @var Psr17Factory */
    protected $httpFactory;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    /** @var ResponseEmitter */
    protected $responseEmitter;

    public function setUp(): void
    {
        putenv('SLACK_SIGNING_KEY=' . self::SIGNING_KEY);
        putenv('SLACK_BOT_TOKEN=' . self::BOT_TOKEN);

        parent::setUp();
        $this->httpFactory = new Psr17Factory();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseEmitter = $this->createMockResponseEmitter();
    }

    protected function parseResponse(ResponseInterface $response): ?array
    {
        $content = (string) $response->getBody();
        if ($content === '') {
            return null;
        }

        try {
            return \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->fail('Could not parse response JSON: ' . $exception->getMessage());
        }
    }

    protected function createCommandRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(http_build_query($data), 'application/x-www-form-urlencoded', $timestamp);
    }

    protected function createInteractiveRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(
            http_build_query(['payload' => json_encode($data)]),
            'application/x-www-form-urlencoded',
            $timestamp
        );
    }

    protected function createEventRequest(array $data, ?int $timestamp = null): ServerRequestInterface
    {
        return $this->createRequest(json_encode($data), 'application/json', $timestamp);
    }

    private function createRequest(string $content, string $contentType, ?int $timestamp = null): ServerRequestInterface
    {
        // Create signature
        $timestamp = $timestamp ?? time();
        $stringToSign = sprintf('v0:%d:%s', $timestamp, $content);
        $signature = 'v0=' . hash_hmac('sha256', $stringToSign, self::SIGNING_KEY);

        return $this->httpFactory->createServerRequest('POST', '/')
            ->withHeader(self::HEADER_TIMESTAMP, (string) $timestamp)
            ->withHeader(self::HEADER_SIGNATURE, $signature)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Length', (string) strlen($content))
            ->withBody($this->httpFactory->createStream($content));
    }

    private function createMockResponseEmitter(): ResponseEmitter
    {
        return new class() implements ResponseEmitter {
            /** @var ResponseInterface|null */
            public $response;

            public function emit(ResponseInterface $response): void
            {
                $this->response = $response;
            }
        };
    }
}
