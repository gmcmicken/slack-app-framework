<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Listener;

/**
 * An intentionally synchronous implementation of AsyncProcessor, that does the additional processing immediately.
 *
 * Since async processing in PHP generally requires additional infrastructure or services, this implementation avoids
 * that by doing the additional processing immediately (before the "ack" is actually sent to Slack). This means that it
 * is a great initial implementation, but will not work if handling the Slack event requires more than 3 seconds.
 */
class PreAckProcessor implements AsyncProcessor
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

    public function process(Context $context): void
    {
        // Run the Slack context through the app/listener again, but this time `isAcknowledged` is set to `true`.
        $context->logger()->debug('Handling payload processing before the ack (synchronously).');
        $this->listener->handle($context);
    }
}
