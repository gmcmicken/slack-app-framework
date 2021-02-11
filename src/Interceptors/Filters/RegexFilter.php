<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors\Filters;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Interceptors\Filter;
use Jeremeamia\Slack\Apps\Context;

class RegexFilter extends Filter
{
    /** @var string */
    private $field;

    /** @var string */
    private $regex;

    /**
     * @param string $field
     * @param string $regex
     * @param Listener|callable|string|null $defaultListener
     */
    public function __construct(string $field, string $regex, $defaultListener = null)
    {
        $this->field = $field;
        $this->regex = $regex;
        parent::__construct($defaultListener);
    }

    public function matches(Context $context): bool
    {
        if (preg_match($this->regex, $context->payload()->get($this->field), $matches)) {
            $context->set('regex', [
                'field' => $this->field,
                'matches' => $matches,
            ]);

            return true;
        }

        return false;
    }
}
