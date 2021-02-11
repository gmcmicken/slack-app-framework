<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors;

use Jeremeamia\Slack\Apps\Coerce;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Listeners\Undefined;
use Jeremeamia\Slack\Apps\Interceptor;

abstract class Filter implements Interceptor
{
    /** @var Listener */
    private $defaultListener;

    /**
     * @param array $fields
     * @param Listener|callable|class-string|null $defaultListener
     * @return Filters\FieldFilter
     */
    public static function fields(array $fields, $defaultListener = null): Filters\FieldFilter
    {
        return new Filters\FieldFilter($fields, $defaultListener);
    }

    /**
     * @param callable $func
     * @param Listener|callable|class-string|null $defaultListener
     * @return Filters\CallbackFilter
     */
    public static function func(callable $func, $defaultListener = null): Filters\CallbackFilter
    {
        return new Filters\CallbackFilter($func, $defaultListener);
    }

    /**
     * @param string $field
     * @param string $regex
     * @param Listener|callable|class-string|null $defaultListener
     * @return Filters\RegexFilter
     */
    public static function regex(string $field, string $regex, $defaultListener = null): Filters\RegexFilter
    {
        return new Filters\RegexFilter($field, $regex, $defaultListener);
    }

    /**
     * @param Listener|callable|class-string|null $defaultListener
     */
    public function __construct($defaultListener = null)
    {
        $this->defaultListener = $defaultListener ? Coerce::listener($defaultListener) : null;
    }

    public function intercept(Context $context, Listener $listener): void
    {
        $listener = ($this->matches($context) ? $listener : $this->defaultListener) ?? new Undefined();
        $listener->handle($context);
    }

    /**
     * @param Context $context
     * @return bool
     */
    abstract public function matches(Context $context): bool;
}
