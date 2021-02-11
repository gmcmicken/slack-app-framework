<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Interceptor;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class Intercepted implements Listener
{
    /** @var Interceptor */
    private $interceptor;

    /** @var Listener */
    private $listener;

    /**
     * @param Interceptor $interceptor
     * @param Listener $listener
     */
    public function __construct(Interceptor $interceptor, Listener $listener)
    {
        $this->interceptor = $interceptor;
        $this->listener = $listener;
    }

    public function handle(Context $context): void
    {
        $this->interceptor->intercept($context, $this->listener);
    }
}
