<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class Async extends AutoAck
{
    /** @var Listener */
    private $asyncListener;

    /** @var Listener|null */
    private $syncListener;

    /**
     * @param Listener $asyncListener
     * @param Listener|null $syncListener
     */
    public function __construct(Listener $asyncListener, ?Listener $syncListener = null)
    {
        $this->asyncListener = $asyncListener;

        if ($syncListener !== null) {
            $this->syncListener = $syncListener;
        }
    }

    protected function handleSync(Context $context): void
    {
        if ($this->syncListener) {
            $this->syncListener->handle($context);
        } else {
            parent::handleSync($context);
        }
    }

    protected function handleAsync(Context $context): void
    {
        $this->asyncListener->handle($context);
    }
}
