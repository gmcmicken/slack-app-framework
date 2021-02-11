<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\BlockKit\Config;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Partials\OptionList;
use Jeremeamia\Slack\BlockKit\Surfaces;

class Blocks
{
    public static function new(): self
    {
        return new self();
    }

    public function appHome(): Surfaces\AppHome
    {
        return Kit::newAppHome();
    }

    public function message(): Surfaces\Message
    {
        return Kit::newMessage();
    }

    public function modal(): Surfaces\Modal
    {
        return Kit::newModal();
    }

    public function optionList(): OptionList
    {
        return OptionList::new();
    }

    public function config(): Config
    {
        return Kit::config();
    }
}
