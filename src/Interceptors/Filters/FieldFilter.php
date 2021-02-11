<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors\Filters;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Interceptors\Filter;
use Jeremeamia\Slack\Apps\Context;

class FieldFilter extends Filter
{
    /** @var array<string, mixed> */
    private $fields;

    /**
     * @param array<string, mixed> $fields
     * @param Listener|callable|class-string|null $defaultListener
     */
    public function __construct(array $fields, $defaultListener = null)
    {
        parent::__construct($defaultListener);
        $this->fields = $fields;
    }

    public function matches(Context $context): bool
    {
        $data = $context->payload();
        foreach ($this->fields as $field => $value) {
            if ($data->get($field) !== $value) {
                return false;
            }
        }

        return true;
    }
}
