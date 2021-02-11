<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps;

interface Listener
{
    public function handle(Context $context): void;
}
