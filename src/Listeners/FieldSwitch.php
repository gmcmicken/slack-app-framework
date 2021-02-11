<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Listeners;

use Closure;
use Jeremeamia\Slack\Apps\Coerce;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

class FieldSwitch implements Listener
{
    /** @var array<string, Listener> */
    private $cases;

    /** @var Listener|null */
    private $default;

    /** @var string string */
    private $field;

    public function __construct(string $field, array $cases, $default = null)
    {
        $default = $default ?? $cases['*'] ?? null;
        if ($default !== null) {
            $this->default = Coerce::listener($default);
            unset($cases['*']);
        }

        $this->field = $field;
        $this->cases = array_map(Closure::fromCallable([Coerce::class, 'listener']), $cases);
    }

    public function handle(Context $context): void
    {
        $value = $context->payload()->get($this->field);
        $listener = $this->cases[$value] ?? $this->default ?? new Undefined();
        $listener->handle($context);
    }
}
