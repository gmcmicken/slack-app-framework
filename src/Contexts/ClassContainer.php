<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\Apps\Exception;
use Psr\Container\ContainerInterface;

use function class_exists;

class ClassContainer implements ContainerInterface
{
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new Exception("Class does not exist: {$id}");
        }

        return new $id();
    }

    public function has($id): bool
    {
        return class_exists($id);
    }
}
