<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * An app Server is a protocol-specific and/or framework-specific app runner.
 *
 * Its main responsibilities include:
 * 1. Receiving an incoming Slack request via the protocol/framework.
 * 2. Authenticating the Slack request.
 * 3. Parsing the Slack request and payload into a Slack `Context`.
 * 4. Using the app to process the Slack context.
 * 5. Providing a protocol-specific way for the app to "ack" back to Slack.
 * 6. Providing a protocol-specific way for the app to "defer" the processing of a context until after the ack.
 */
abstract class Server
{
    /** @var Listener */
    protected $app;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * AppServer constructor.
     *
     * @param Listener|callable|class-string|null $app
     * @param LoggerInterface|null $logger
     */
    final public function __construct($app = null, LoggerInterface $logger = null)
    {
        $this->withApp($app ?? new Listeners\NoOp());
        $this->withLogger($logger ?? new NullLogger());
    }

    /**
     * @param Listener|callable|class-string $app
     * @return static
     */
    public function withApp($app): self
    {
        $this->app = Coerce::listener($app);

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

    /**
     * Starts receiving and processing requests from Slack.
     */
    abstract public function start(): void;

    /**
     * Stops receiving requests from Slack.
     *
     * Depending on the implementation, `stop()` may not need to actually do anything.
     */
    abstract public function stop(): void;
}
