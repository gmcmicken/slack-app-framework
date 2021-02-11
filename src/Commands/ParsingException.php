<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Commands;

use Jeremeamia\Slack\Apps\Exception;

class ParsingException extends Exception
{
    public function __construct($message, array $values = [])
    {
        parent::__construct(vsprintf($message, $values));
    }
}
