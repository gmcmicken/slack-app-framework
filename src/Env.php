<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

final class Env
{
    private const APP_TOKEN = 'SLACK_APP_TOKEN';
    private const APP_ID = 'SLACK_APP_ID';
    private const BOT_TOKEN = 'SLACK_BOT_TOKEN';
    private const CLIENT_ID = 'SLACK_CLIENT_ID';
    private const CLIENT_SECRET = 'SLACK_CLIENT_SECRET';
    private const FIVE_MINUTES = 60 * 5;
    private const MAX_CLOCK_SKEW = 'SLACK_MAX_CLOCK_SKEW';
    private const SIGNING_KEY = 'SLACK_SIGNING_KEY';
    private const SKIP_AUTH = 'SLACK_SKIP_AUTH';

    public static function getAppToken(): ?string
    {
        return self::get(self::APP_TOKEN);
    }

    public static function getAppId(): ?string
    {
        return self::get(self::APP_ID);
    }

    public static function getBotToken(): ?string
    {
        return self::get(self::BOT_TOKEN);
    }

    public static function getClientId(): ?string
    {
        return self::get(self::CLIENT_ID);
    }

    public static function getClientSecret(): ?string
    {
        return self::get(self::CLIENT_SECRET);
    }

    public static function getMaxClockSkew(): int
    {
        $value = self::get(self::MAX_CLOCK_SKEW);

        return $value ? (int) $value : self::FIVE_MINUTES;
    }

    public static function getSigningKey(): ?string
    {
        return self::get(self::SIGNING_KEY);
    }

    public static function getSkipAuth(): bool
    {
        return (bool) self::get(self::SKIP_AUTH);
    }

    private static function get(string $key): ?string
    {
        return getenv($key, true) ?: getenv($key) ?: null;
    }
}
