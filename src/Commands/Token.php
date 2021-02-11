<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Commands;

class Token
{
    /** @var bool */
    private $isOpt;

    /** @var string|null */
    private $value;

    /** @var string|null */
    private $key;

    public function __construct(string $token)
    {
        $len = strlen($token);
        if ($len >= 2 && substr($token, 0, 2) === '--') {
            $this->isOpt = true;
            [$this->key, $this->value] = array_pad(explode('=', substr($token, 2), 2), 2, null);
        } elseif ($len > 1 && $token[0] === '-') {
            $this->isOpt = true;
            $this->key = $token[1];
            $this->value = ($len > 2) ? substr($token, 2) : null;
            if ($this->value && $this->value[0] === '=') {
                $this->value = substr($this->value, 1);
            }
        } else {
            $this->isOpt = false;
            $this->value = $token;
        }
    }

    public function resolveValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function isOpt(): bool
    {
        return $this->isOpt;
    }
}
