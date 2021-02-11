<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Interceptors;

use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Listener;
use Jeremeamia\Slack\Apps\Interceptor;
use Jeremeamia\Slack\Apps\Contexts\PayloadType;

class UrlVerification implements Interceptor
{
    public function intercept(Context $context, Listener $listener): void
    {
        $payload = $context->payload();
        if ($payload->isType(PayloadType::urlVerification())) {
            $challenge = (string) $payload->get('challenge', true);
            $context->ack(compact('challenge'));
        } else {
            $listener->handle($context);
        }
    }
}
