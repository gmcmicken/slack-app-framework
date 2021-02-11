<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class Undefined implements Listener
{
    public function handle(Context $context): void
    {
        $context->logger()->info('No listener matching payload', [
            'payload' => $context->payload()->toArray(),
        ]);

        if (!$context->isAcknowledged()) {
            $context->ack();
        }
    }
}
