<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

use Jeremeamia\Slack\BlockKit\Surfaces;

final class Coerce
{
    /**
     * @param Surfaces\Message|array|string $message
     * @return Surfaces\Message
     * @internal
     */
    public static function message($message): Surfaces\Message
    {
        if ($message instanceof Surfaces\Message) {
            return $message;
        } elseif (is_string($message)) {
            return Surfaces\Message::new()->text($message);
        } elseif (is_array($message)) {
            return Surfaces\Message::fromArray($message);
        }

        throw new Exception('Invalid message content');
    }

    /**
     * @param Surfaces\Modal|array|string $modal
     * @return Surfaces\Modal
     * @internal
     */
    public static function modal($modal): Surfaces\Modal
    {
        if ($modal instanceof Surfaces\Modal) {
            return $modal;
        } elseif (is_string($modal)) {
            return Surfaces\Modal::new()->title('Thanks')->text($modal);
        } elseif (is_array($modal)) {
            return Surfaces\Modal::fromArray($modal);
        }

        throw new Exception('Invalid modal content');
    }

    /**
     * @param Surfaces\AppHome|array|string $appHome
     * @return Surfaces\AppHome
     * @internal
     */
    public static function appHome($appHome): Surfaces\AppHome
    {
        if ($appHome instanceof Surfaces\AppHome) {
            return $appHome;
        } elseif (is_string($appHome)) {
            return Surfaces\AppHome::new()->text($appHome);
        } elseif (is_array($appHome)) {
            return Surfaces\AppHome::fromArray($appHome);
        }

        throw new Exception('Invalid app home content');
    }

    /**
     * @param Listener|callable|array|class-string $listener
     * @return Listener
     * @internal
     */
    public static function listener($listener): Listener
    {
        if ($listener instanceof Listener) {
            return $listener;
        } elseif (is_string($listener)) {
            return new Listeners\ClassResolver($listener);
        } elseif (is_callable($listener)) {
            return new Listeners\Callback($listener);
        } elseif (is_array($listener)) {
            $interceptors = $listener;
            $listener = array_pop($interceptors);
            return new Interceptors\Chain($interceptors, $listener);
        }

        throw new Exception('Invalid listener');
    }
}
