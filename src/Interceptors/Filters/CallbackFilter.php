<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors\Filters;

use Closure;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Interceptors\Filter;
use Jeremeamia\Slack\Apps\Context;

class CallbackFilter extends Filter
{
    /** @var Closure */
    private $filterFn;

    /**
     * @param callable $filterFn
     * @param Listener|callable|string|null $defaultListener
     */
    public function __construct(callable $filterFn, $defaultListener = null)
    {
        $this->filterFn = $filterFn instanceof Closure ? $filterFn : Closure::fromCallable($filterFn);
        parent::__construct($defaultListener);
    }

    public function matches(Context $context): bool
    {
        return ($this->filterFn)($context);
    }
}
