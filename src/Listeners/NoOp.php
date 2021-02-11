<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class NoOp implements Listener
{
    public function handle(Context $context): void
    {
        $context->ack();
    }
}
