<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use JsonSerializable;

interface AckClient
{
    /**
     * @param JsonSerializable|null $message Message to send with the ack. Message is not always needed.
     */
    public function ack(?JsonSerializable $message = null): void;
}
