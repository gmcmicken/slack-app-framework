<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Auth;

use Jeremeamia\Slack\Apps\Exception;

interface TokenStore
{
    /**
     * @param string $teamId
     * @param string|null $enterpriseId
     * @return string
     * @throws Exception if bot token is not available
     */
    public function get(string $teamId, ?string $enterpriseId): ?string;

    /**
     * @param string $teamId
     * @param string|null $enterpriseId
     * @param string $botToken
     */
    public function set(string $teamId, ?string $enterpriseId, string $botToken): void;
}
