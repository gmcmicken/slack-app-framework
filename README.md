# Slack App Framework for PHP

A small, PHP framework for building Slack Apps. Takes inspiration from Slack's Bolt frameworks.

## Under Development

:warning: This is under heavy development. _Breaking changes may occur on any commit._

## Example

```php
<?php

declare(strict_types=1);

use Jeremeamia\Slack\Apps\App;
use Jeremeamia\Slack\Apps\Context;

// Note: Expects SLACK_SIGNING_KEY and SLACK_BOT_TOKEN to be set in environment.

App::new()
    ->command('slack-test', function (Context $ctx) {
        $ctx->respond(':thumbsup: *Success*');
    })
    ->globalShortcut('shortcut_test', function (Context $ctx) {
        $modal = $ctx->blocks()->modal()
            ->title('Hello')
            ->text(':wave: Hello from a *Global Shortcut*.');
        $ctx->modals()->open($modal);
    })
    ->messageShortcut('message_action_test', function (Context $ctx) {
        $ctx->respond(':wave: Hello from a *Message Action*.');
    })
    ->blockSuggestion('custom_options', function (Context $ctx) {
        $ctx->options([
            'Option 1' => 'foo',
            'Option 2' => 'bar',
            'Option 3' => 'baz',
        ]);
    })
    ->blockAction('test-button', function (Context $ctx) {
        $action = $ctx->payload()->get('actions.0');
        $msg = $ctx->blocks()->message();
        $msg->newTwoColumnTable()
            ->caption('*Action*')
            ->row('`type`', $action['type'])
            ->row('`block_id`', $action['block_id'])
            ->row('`action_id`', $action['action_id'])
            ->row('`value`', $action['value']);
        $ctx->respond($msg);
    })
    ->event('app_home_opened', function (Context $ctx) {
        $user = $ctx->fmt()->user($ctx->payload()->get('event.user'));
        $home = $ctx->blocks()->appHome()->text(":wave: Hello, {$user}! This is your *App Home*.");
        $ctx->home($home);
    })
    ->run();
```

## Handling Requests with the `Context` Object

```
$context

  // To respond (ack) to incoming Slack request:
    ->ack(Message|array|string|null)  // Responds to request with 200 (and optional message)
    ->options(OptionList|array|null)  // Responds to request with an options list
    ->view()
      ->clear()                       // Responds to modal submission by clearing modal stack
      ->close()                       // Responds to modal submission by clearing current modal
      ->errors(array)                 // Responds to modal submission by providing form errors
      ->push(Modal|array|string)      // Responds to modal submission by pushing new modal to stack
      ->update(Modal|array|string)    // Responds to modal submission by updating current modal

  // To call Slack APIs (to send messages, open/update modals, etc.) after the ack:
    ->respond(Message|array|string)   // Responds to message. Uses payload.response_url
    ->say(Message|array|string)       // Responds in channel. Uses API and payload.channel.id
    ->modals()
      ->open(Modal|array|string)      // Opens a modal. Uses API and payload.trigger_id
      ->push(Modal|array|string)      // Pushes a new modal. Uses API and payload.trigger_id
      ->update(Modal|array|string)    // Updates a modal. Uses API and payload.view.id
    ->home(AppHome|array|string)      // Modifies App Home for user. Uses API and payload.user.id
    ->api()->{$method}(...$args)      // Use Slack API client for arbitrary API operations

  // Additional helpers
    ->payload()                       // Returns the payload of the incoming request from Slack
    ->blocks()                        // Returns an object that provides ability to create BlockKit objects
    ->fmt()                           // Returns the block kit formatter
    ->logger()                        // Returns an instance of a PSR-3 logger
    ->container()                     // Returns an instance of a PSR-11 container
    ->get(string)                     // Returns a value from the context
    ->set(string, mixed)              // Sets a value in the context
    ->isAcknowledged()                // Returns true if ack has been sent
```

## High Level Design

![UML diagram of the framework](https://yuml.me/d4cd353d.png)

<details>
<summary>YUML Source</summary>
<pre>
[Server]-creates>[Context]
[Server]<>->[Listener]
[Listener]^[Application]
[Listener]handles->[Context]
[Context]<>->[Payload]
[Context]<>->[_Clients_;AckClient;ResponseClient;ApiClient]
[Context]<>->[_Helpers_;Logger;BlockKit;Modals;View]
[Application]<>->[Router]
[Router]^[App (façade)]
[App (façade)]-creates>[Application]
[App (façade)]-creates>[Server]
[Application]<>->[Container]
[Context]<>->[Container]
[Router]<>->[_Listeners_]
[Router]<>->[_Interceptors_]
</pre>
</details>

## Standards

- PSR-1, PSR-12: Coding Style
- PSR-3: Logger Interface
- PSR-4: Autoloading
- PSR-7, PSR-15, PSR-17, PSR-18: HTTP
- PSR-11: Container Interface
