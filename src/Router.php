<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Jeremeamia\Slack\Apps\Contexts\PayloadType;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use JsonSerializable;

/**
 * Routes app contexts by their payload type and IDs.
 */
class Router
{
    private const DEFAULT = '_default';

    /** @var Message|null */
    private $commandAck;

    /** @var array<string, array<string, Listener>> */
    private $listeners;

    /** @var Interceptors\Chain */
    private $interceptors;

    /** @var bool */
    private $urlVerificationAdded = false;

    /**
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    final public function __construct()
    {
        $this->listeners = [];
        $this->interceptors = Interceptors\Chain::new();
    }

    /**
     * @param JsonSerializable|array|string $ack
     * @return static
     */
    public function withCommandAck($ack): self
    {
        $this->commandAck = Coerce::message($ack);

        return $this;
    }

    /**
     * @return static
     */
    public function withUrlVerification(): self
    {
        if (!$this->urlVerificationAdded) {
            $this->interceptors->add(new Interceptors\UrlVerification(), true);
            $this->urlVerificationAdded = true;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function command(string $name, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::command(), $name, $listener, $asyncListener);
    }

    /**
     * @param string $name
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function commandAsync(string $name, $asyncListener): self
    {
        $syncListener = function (Context $context): void {
            $context->ack($this->commandAck);
        };

        return $this->register(PayloadType::command(), $name, $syncListener, $asyncListener);
    }

    /**
     * @param string $name
     * @param array<string, Listener|callable|array|class-string> $subCommands
     * @return static
     */
    public function commandGroup(string $name, array $subCommands): self
    {
        $syncListener = new Commands\CommandRouter($subCommands);

        return $this->register(PayloadType::command(), $name, $syncListener, null);
    }

    /**
     * @param string $name
     * @param array<string, Listener|callable|array|class-string> $subCommands
     * @return static
     */
    public function commandGroupAsync(string $name, array $subCommands): self
    {
        $asyncListener = new Commands\CommandRouter($subCommands);
        $syncListener = function (Context $context): void {
            $context->ack($this->commandAck);
        };

        return $this->register(PayloadType::command(), $name, $syncListener, $asyncListener);
    }

    /**
     * @param string $name
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function event(string $name, $listener, $asyncListener = null): self
    {
        return $this->withUrlVerification()->register(PayloadType::eventCallback(), $name, $listener, $asyncListener);
    }

    /**
     * @param string $name
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function eventAsync(string $name, $asyncListener): self
    {
        return $this->withUrlVerification()->register(PayloadType::eventCallback(), $name, null, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function globalShortcut(string $callbackId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::shortcut(), $callbackId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function globalShortcutAsync(string $callbackId, $asyncListener): self
    {
        return $this->register(PayloadType::messageAction(), $callbackId, null, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function messageShortcut(string $callbackId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::messageAction(), $callbackId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function messageShortcutAsync(string $callbackId, $asyncListener): self
    {
        return $this->register(PayloadType::messageAction(), $callbackId, null, $asyncListener);
    }

    /**
     * @param string $actionId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function blockAction(string $actionId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::blockActions(), $actionId, $listener, $asyncListener);
    }

    /**
     * @param string $actionId
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function blockActionAsync(string $actionId, $asyncListener): self
    {
        return $this->register(PayloadType::blockActions(), $actionId, null, $asyncListener);
    }

    /**
     * @param string $actionId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function blockSuggestion(string $actionId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::blockSuggestion(), $actionId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function viewSubmission(string $callbackId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::viewSubmission(), $callbackId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function viewClosed(string $callbackId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::viewClosed(), $callbackId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function viewClosedAsync(string $callbackId, $asyncListener): self
    {
        return $this->register(PayloadType::viewClosed(), $callbackId, null, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function workflowStepEdit(string $callbackId, $listener, $asyncListener = null): self
    {
        return $this->register(PayloadType::workflowStepEdit(), $callbackId, $listener, $asyncListener);
    }

    /**
     * @param string $callbackId
     * @param Listener|callable|array|class-string $asyncListener
     * @return static
     */
    public function workflowStepEditAsync(string $callbackId, $asyncListener): self
    {
        return $this->register(PayloadType::workflowStepEdit(), $callbackId, null, $asyncListener);
    }

    /**
     * @param PayloadType|string $type
     * @param Listener|callable|array|class-string $listener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    public function on($type, $listener, $asyncListener = null): self
    {
        return $this->register($type, self::DEFAULT, $listener, $asyncListener);
    }

    /**
     * @param PayloadType|string $type
     * @param Listener|callable|class-string $asyncListener
     * @return static
     */
    public function onAsync($type, $asyncListener): self
    {
        return $this->register($type, self::DEFAULT, null, $asyncListener);
    }

    /**
     * @return Interceptors\Chain
     */
    public function interceptors(): Interceptors\Chain
    {
        return $this->interceptors;
    }

    /**
     * @param Context $context
     * @return Listener
     */
    public function getListener(Context $context): Listener
    {
        $type = (string) $context->payload()->getType();
        $id = $context->payload()->getTypeId() ?? self::DEFAULT;
        $listener = $this->listeners[$type][$id] ?? $this->listeners[$type][self::DEFAULT] ?? new Listeners\Undefined();

        return new Listeners\Intercepted($this->interceptors, $listener);
    }

    /**
     * @param PayloadType|string $type
     * @param string $name
     * @param Listener|callable|array|class-string|null $syncListener
     * @param Listener|callable|array|class-string|null $asyncListener
     * @return static
     */
    private function register($type, string $name, $syncListener, $asyncListener): self
    {
        $type = (string) $type;
        $name = trim($name, '/ ');
        $syncListener = $syncListener ? Coerce::listener($syncListener) : null;
        $asyncListener = $asyncListener ? Coerce::listener($asyncListener) : null;

        if ($asyncListener !== null) {
            $listener = new Listeners\Async($asyncListener, $syncListener);
        } elseif ($syncListener !== null) {
            $listener = new Listeners\Sync($syncListener);
        } else {
            $listener = new Listeners\Undefined();
        }

        $this->listeners[$type][$name] = $listener;

        return $this;
    }
}
