<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Commands;

use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Context;

abstract class CommandListener implements Listener
{
    private static $definitions = [];

    abstract protected static function buildDefinition(DefinitionBuilder $builder): DefinitionBuilder;

    public static function getDefinition(): Definition
    {
        if (!isset(self::$definitions[static::class])) {
            self::$definitions[static::class] = static::buildDefinition(new DefinitionBuilder())->build();
        }

        return self::$definitions[static::class];
    }

    abstract protected function listenToCommand(Context $context, Input $input): void;

    public function handle(Context $context): void
    {
        $definition = $this->getDefinition();

        try {
            $input = new Input($context->payload()->get('text'), $definition);
            $this->listenToCommand($context, $input);
        } catch (ParsingException $ex) {
            $message = $definition->getHelpMessage($ex->getMessage());
            if ($context->isAcknowledged()) {
                $context->respond($message);
            } else {
                $context->ack($message);
            }
        }
    }
}
