<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Auth;

use function abs;
use function hash_equals;
use function hash_hmac;
use function sprintf;
use function substr;
use function time;

class AuthV0
{
    public const HEADER_SIGNATURE = 'X-Slack-Signature';
    public const HEADER_TIMESTAMP = 'X-Slack-Request-Timestamp';

    /**
     * Validate a request timestamp.
     *
     * @param int $timestamp
     * @param int $maxClockSkew
     */
    public static function validateTimestamp(int $timestamp, int $maxClockSkew = 5 * 60): void
    {
        if (abs(time() - $timestamp) > $maxClockSkew) {
            throw new AuthException('Timestamp is too old or too new.');
        }
    }

    /**
     * Validate a request signature using "v0".
     *
     * @param int $timestamp
     * @param string $content
     * @param string $signature
     * @param string $key
     */
    public static function validateSignature(int $timestamp, string $content, string $signature, string $key): void
    {
        if (substr($signature, 0, 3) !== 'v0=') {
            throw new AuthException('Missing or unsupported signature version');
        }

        $stringToSign = sprintf('v0:%d:%s', $timestamp, $content);
        $expectedSignature = 'v0=' . hash_hmac('sha256', $stringToSign, $key);

        if (!hash_equals($signature, $expectedSignature)) {
            throw new AuthException('Signature (v0) failed verification');
        }
    }
}
