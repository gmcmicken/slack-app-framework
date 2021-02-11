<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * A Slack application.
 *
 * Processes Slack events by routing to listeners that are registered by event payload type. Can be run by an AppServer,
 * or can use an AppServer to run itself.
 */
class App extends Router
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerInterface */
    private $container;

    private const DEFAULT_SERVER_IMPLEMENTATION = 'Jeremeamia\\Slack\\Apps\\Http\\HttpServer';

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function withContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Compiles and returns the Application, which can be used directly as a Listener.
     *
     * @return Application
     */
    public function build(): Application
    {
        return new Application($this->container, $this);
    }

    /**
     * Runs the app using the provided AppServer.
     *
     * Defaults to the HttpServer, if available.
     *
     * @param Server|null $server
     */
    public function run(?Server $server = null): void
    {
        // Default to the basic HTTP server which gets data from superglobals.
        if (!$server) {
            $class = self::DEFAULT_SERVER_IMPLEMENTATION;
            if (!class_exists($class)) {
                throw new Exception('No default server implementation available.');
            }

            $server = new $class();
        }

        // Set logger for server if set for app.
        if ($this->logger !== null) {
            $server->withLogger($this->logger);
        }

        // Start the server with this app.
        $server->withApp($this->build())->start();
    }
}
