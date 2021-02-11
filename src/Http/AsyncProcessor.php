<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Context;

/**
 * Performs additional processing on a context after the initial "ack", in order to avoid Slack's 3-second timeout.
 */
interface AsyncProcessor
{
    /**
     * Perform additional processing on an "ack"ed context, usually via a queuing mechanism.
     *
     * This additional processing is asynchronous and happens "out of band" from the original Slack request.
     *
     * @param Context $context
     */
    public function process(Context $context): void;
}
