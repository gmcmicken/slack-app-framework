<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

abstract class AutoAck implements Listener
{
    public function handle(Context $context): void
    {
        if ($context->isAcknowledged()) {
            $this->handleAsync($context);
        } else {
            $this->handleSync($context);
            $context->defer();
        }
    }

    protected function handleSync(Context $context): void
    {
        $context->ack();
    }

    abstract protected function handleAsync(Context $context): void;
}
