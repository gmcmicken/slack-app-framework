<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

use function json_encode;

class StderrLogger extends AbstractLogger
{
    private const LOG_LEVEL_MAP = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    /** @var int */
    private $minLevel;

    /** @var string */
    private $name;

    /** @var resource */
    private $stream;

    public function __construct(string $minLevel = LogLevel::DEBUG, string $name = 'App', $stream = 'php://stderr')
    {
        if (!isset(self::LOG_LEVEL_MAP[$minLevel])) {
            throw new InvalidArgumentException("Invalid log level: {$minLevel}");
        }

        $this->minLevel = self::LOG_LEVEL_MAP[$minLevel];
        $this->name = $name;

        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->stream = fopen($stream, 'a');
            if (!$this->stream) {
                throw new Exception('Unable to open stream: ' . $stream);
            }
        } else {
            throw new InvalidArgumentException('A stream must either be a resource or a string');
        }
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::LOG_LEVEL_MAP[$level])) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }

        // Don't report logs for log levels less than the min level.
        if (self::LOG_LEVEL_MAP[$level] < $this->minLevel) {
            return;
        }

        // Add prefix to the message.
        $message = "[{$this->name}] {$message}";

        // Apply special formatting for "exception" fields.
        if (isset($context['exception'])) {
            $context['exception'] = explode("\n", (string) $context['exception']);
        }

        fwrite($this->stream, json_encode(compact('level', 'message', 'context')) . "\n");
    }
}
