<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class Sync implements Listener
{
    /** @var Listener */
    private $listener;

    /**
     * @param Listener $listener
     */
    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    public function handle(Context $context): void
    {
        $this->listener->handle($context);
        if (!$context->isAcknowledged()) {
            $context->ack();
        }
    }
}
