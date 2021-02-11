<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use JsonSerializable;

class DataBag implements JsonSerializable
{
    use HasData;
}
