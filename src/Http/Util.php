<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Contexts\Payload;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlerInterface;

use function json_decode;
use function parse_str;
use function urldecode;

abstract class Util
{
    /**
     * Wraps handler with middleware, but keeps the handler interface.
     *
     * @param HandlerInterface $handler
     * @param MiddlewareInterface[] $middlewares
     * @return HandlerInterface
     */
    public static function applyMiddleware(HandlerInterface $handler, array $middlewares): HandlerInterface
    {
        foreach ($middlewares as $middleware) {
            $handler = new class($handler, $middleware) implements HandlerInterface {
                /** @var HandlerInterface */
                private $handler;

                /** @var MiddlewareInterface */
                private $middleware;

                public function __construct(HandlerInterface $handler, MiddlewareInterface $middleware)
                {
                    $this->handler = $handler;
                    $this->middleware = $middleware;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->handler);
                }
            };
        }

        return $handler;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Payload
     * @throws HttpException if payload cannot be parsed.
     */
    public static function parsePayloadFromRequest(ServerRequestInterface $request): Payload
    {
        // Parse body based on Content-Type.
        $body = self::readRequestBody($request);

        try {
            switch ($request->getHeaderLine('Content-Type')) {
                case 'application/x-www-form-urlencoded':
                    parse_str($body, $data);
                    break;
                case 'application/json':
                    $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    break;
                default:
                    throw new HttpException('Unsupported request body format', 400);
            }

            // Parse embedded payloads.
            if (isset($data['payload'])) {
                $data = json_decode(urldecode($data['payload']), true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $ex) {
            throw new HttpException('Could not parse json for payload', 400, $ex);
        }

        return new Payload($data);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function readRequestBody(ServerRequestInterface $request): string
    {
        if ($request->getMethod() !== 'POST') {
            throw new HttpException("Request method \"{$request->getMethod()}\" not allowed", 405);
        }

        $body = $request->getBody();
        $bodyContent = (string) $body;
        $body->rewind();

        if (empty($bodyContent)) {
            throw new HttpException('Request body is empty', 400);
        }

        return $bodyContent;
    }
}
