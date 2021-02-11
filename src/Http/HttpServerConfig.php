<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Auth\SingleTokenStore;
use Jeremeamia\Slack\Apps\Auth\TokenStore;
use Jeremeamia\Slack\Apps\Env;

class HttpServerConfig
{
    /** @var string|null */
    private $botToken;

    /** @var TokenStore|null */
    private $tokenStore;

    /** @var string|null */
    private $signingKey;

    /** @var int|null */
    private $maxClockSkew;

    /** @var AsyncProcessor|null */
    private $asyncProcessor;

    /** @var ResponseEmitter|null */
    private $emitter;

    public static function new(): self
    {
        return new self();
    }

    public function setAsyncProcessor(AsyncProcessor $asyncProcessor): self
    {
        $this->asyncProcessor = $asyncProcessor;

        return $this;
    }

    public function getAsyncProcessor(): ?AsyncProcessor
    {
        return $this->asyncProcessor;
    }

    public function setBotToken(string $botToken): self
    {
        $this->botToken = $botToken;

        return $this;
    }

    public function getBotToken(): string
    {
        $this->botToken = $this->botToken ?? Env::getBotToken();

        if ($this->botToken === null) {
            throw new HttpException('Bot token not set for App');
        }

        return $this->botToken;
    }

    public function setTokenStore(TokenStore $tokenStore): self
    {
        $this->tokenStore = $tokenStore;

        return $this;
    }

    public function getTokenStore(): TokenStore
    {
        if (!$this->tokenStore) {
            $this->tokenStore = new SingleTokenStore($this->botToken);
        }

        return $this->tokenStore;
    }

    public function setSigningKey(string $signingKey): self
    {
        $this->signingKey = $signingKey;

        return $this;
    }

    public function getSigningKey(): string
    {
        $this->signingKey = $this->signingKey ?? Env::getSigningKey();

        if ($this->signingKey === null) {
            throw new HttpException('Signing key not set for App');
        }

        return $this->signingKey;
    }

    public function setMaxClockSkew(int $maxClockSkew): self
    {
        $this->maxClockSkew = $maxClockSkew;

        return $this;
    }

    public function getMaxClockSkew(): int
    {
        return $this->maxClockSkew ?? Env::getMaxClockSkew();
    }

    public function setResponseEmitter(ResponseEmitter $emitter): self
    {
        $this->emitter = $emitter;

        return $this;
    }

    public function getResponseEmitter(): ResponseEmitter
    {
        if (!$this->emitter) {
            $this->emitter = new EchoResponseEmitter();
        }

        return $this->emitter;
    }
}
