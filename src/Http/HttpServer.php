<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Server;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlerInterface;
use Throwable;

class HttpServer extends Server
{
    /** @var HttpServerConfig|null */
    private $config;

    /** @var ServerRequestInterface|null */
    private $request;

    public function withConfig(HttpServerConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function withRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Starts receiving and processing requests from Slack.
     */
    public function start(): void
    {
        try {
            $request = $this->getRequest();
            $response = $this->getHandler()->handle($request);
        } catch (Throwable $exception) {
            $response = new Response($exception->getCode() ?: 500);
            $this->logger->error('Error responding to incoming Slack request', [
                'server' => self::class,
                'exception' => $exception,
            ]);
        }

        $this->getConfig()->getResponseEmitter()->emit($response);
    }

    /**
     * Stops receiving requests from Slack.
     *
     * Depending on the implementation, `stop()` may not need to do anything.
     */
    public function stop(): void
    {
        // No action necessary, since PHP's typical request lifecycle ends automatically.
    }

    private function getConfig(): HttpServerConfig
    {
        if (!$this->config) {
            $this->config = new HttpServerConfig();
        }

        return $this->config;
    }

    /**
     * Gets a representation of the request data from super globals.
     *
     * @return ServerRequestInterface
     */
    private function getRequest(): ServerRequestInterface
    {
        if (!$this->request) {
            try {
                $httpFactory = new Psr17Factory();
                $requestFactory = new ServerRequestCreator($httpFactory, $httpFactory, $httpFactory, $httpFactory);
                $this->request = $requestFactory->fromGlobals();
            } catch (Throwable $ex) {
                throw new HttpException('Invalid Slack request', 400, $ex);
            }
        }

        return $this->request;
    }

    /**
     * Gets a request handler for the Slack app.
     *
     * @return HandlerInterface
     */
    private function getHandler(): HandlerInterface
    {
        $cfg = $this->getConfig();
        $handler = new AppHandler($this->app, $cfg->getTokenStore(), $cfg->getAsyncProcessor(), $this->logger);
        $authMiddleware = new AuthMiddleware($cfg->getSigningKey(), $cfg->getMaxClockSkew());

        return Util::applyMiddleware($handler, [$authMiddleware]);
    }
}
