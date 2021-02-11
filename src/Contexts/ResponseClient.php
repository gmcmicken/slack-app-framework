<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\Apps\Exception;
use JsonSerializable;

interface ResponseClient
{
    /**
     * Sends a response to a Slack message using a response_url.
     *
     * @param string $responseUrl URL used to respond to Slack message
     * @param JsonSerializable $message Message to respond with
     * @throws Exception if responding was not successful
     */
    public function respond(string $responseUrl, JsonSerializable $message): void;
}
