<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors;

use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Interceptor;
use Jeremeamia\Slack\Apps\Listeners\Intercepted;

class Chain implements Interceptor
{
    /** @var Interceptor[] */
    private $interceptors = [];

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param Interceptor[] $interceptors
     */
    public function __construct(array $interceptors = [])
    {
        $this->addMultiple($interceptors);
    }

    public function add(Interceptor $interceptor, bool $prepend = false): self
    {
        if ($interceptor instanceof self) {
            return $this->addMultiple($interceptor->interceptors, $prepend);
        }

        if ($prepend) {
            array_unshift($this->interceptors, $interceptor);
        } else {
            $this->interceptors[] = $interceptor;
        }

        return $this;
    }

    public function addMultiple(array $interceptors, bool $prepend = false): self
    {
        foreach ($interceptors as $interceptor) {
            $this->add($interceptor, $prepend);
        }

        return $this;
    }

    public function intercept(Context $context, Listener $listener): void
    {
        while ($interceptor = array_pop($this->interceptors)) {
            $listener = new Intercepted($interceptor, $listener);
        }

        $listener->handle($context);
    }
}
