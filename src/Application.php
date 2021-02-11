<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Jeremeamia\Slack\Apps\Contexts\ClassContainer;
use Psr\Container\ContainerInterface;

/**
 * A Slack application.
 *
 * Processes Slack events by routing to listeners that are registered by event payload type. Can be run by an AppServer,
 * or can use an AppServer to run itself.
 */
class Application implements Listener
{
    /** @var Router */
    private $router;

    /** @var ContainerInterface */
    private $container;

    public function __construct(?ContainerInterface $container = null, ?Router $router = null)
    {
        $this->container = $container ?? $this->configureContainer();
        $this->router = $router ?? $this->configureRouter();
    }

    public function handle(Context $context): void
    {
        $context->withContainer($this->container);
        $this->router->getListener($context)->handle($context);
    }

    /**
     * Configures the router.
     *
     * This can be overridden by a specific application instance.
     *
     * @return Router
     */
    protected function configureRouter(): Router
    {
        return new Router();
    }

    /**
     * Configures the container.
     *
     * This can be overridden by a specific application instance.
     *
     * @return ContainerInterface
     */
    protected function configureContainer(): ContainerInterface
    {
        return new ClassContainer();
    }
}
