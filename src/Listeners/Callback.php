<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Closure;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class Callback implements Listener
{
    /** @var Closure */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);
    }

    public function handle(Context $context): void
    {
        ($this->callback)($context);
    }
}
