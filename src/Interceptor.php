<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

interface Interceptor
{
    public function intercept(Context $context, Listener $listener): void;
}
