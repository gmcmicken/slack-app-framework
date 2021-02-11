<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Exception;
use Throwable;

class HttpException extends Exception
{
    public function __construct(string $message, int $statusCode = 500, Throwable $previous = null)
    {
        parent::__construct("HTTP error: {$message}", $statusCode, $previous);
    }
}
