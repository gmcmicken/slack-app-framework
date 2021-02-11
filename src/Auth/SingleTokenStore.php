<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Auth;

use Jeremeamia\Slack\Apps\Env;
use Jeremeamia\Slack\Apps\Exception;

class SingleTokenStore implements TokenStore
{
    /** @var string|null */
    private $botToken;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?? Env::getBotToken();
    }

    public function get(string $teamId, ?string $enterpriseId): string
    {
        if ($this->botToken === null) {
            throw new Exception('No bot token available: Bot token is null or is missing from environment');
        }

        return $this->botToken;
    }

    public function set(string $teamId, ?string $enterpriseId, string $botToken): void
    {
        throw new Exception('Cannot change bot token in SingleTokenStore');
    }
}
