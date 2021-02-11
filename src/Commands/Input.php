<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Commands;

use Jeremeamia\Slack\Apps\Contexts\HasData;

class Input
{
    use HasData;

    /** @var Definition */
    private $definition;

    public function __construct(string $commandText, Definition $definition)
    {
        $this->definition = $definition;
        $parser = new Parser($this->definition);
        $this->setData($parser->parse($commandText));
    }

    /**
     * @return Definition
     */
    public function getDefinition(): Definition
    {
        return $this->definition;
    }
}
