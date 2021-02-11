<?php

namespace Jeremeamia\Slack\Apps\Tests\Integration;

use Jeremeamia\Slack\Apps\App;
use Jeremeamia\Slack\Apps\Commands\CommandListener;
use Jeremeamia\Slack\Apps\Commands\DefinitionBuilder;
use Jeremeamia\Slack\Apps\Commands\Input;
use Jeremeamia\Slack\Apps\Context;
use Jeremeamia\Slack\Apps\Http\HttpServer;
use Jeremeamia\Slack\Apps\Http\HttpServerConfig;

class CommandTest extends IntegTestCase
{
    public function testCanHandleCommandRequest(): void
    {
        $request = $this->createCommandRequest([
            'command' => '/test',
            'text' => 'hello',
        ]);

        $this->logger->expects($this->never())->method('error');
        $config = HttpServerConfig::new()->setResponseEmitter($this->responseEmitter);
        $server = HttpServer::new()->withRequest($request)->withConfig($config);

        App::new()
            ->withLogger($this->logger)
            ->command('test', function (Context $ctx) {
                $payload = $ctx->payload();
                $ctx->ack("{$payload->get('command')} {$payload->get('text')}");
            })
            ->run($server);

        $result = $this->parseResponse($this->responseEmitter->response);
        $this->assertEquals('/test hello', $result['blocks'][0]['text']['text'] ?? null);
    }

    public function testCanHandleSubCommandRequest(): void
    {
        $request = $this->createCommandRequest([
            'command' => '/test',
            'text' => 'hello Jeremy --caps',
        ]);

        $this->logger->expects($this->never())->method('error');
        $config = HttpServerConfig::new()->setResponseEmitter($this->responseEmitter);
        $server = HttpServer::new()->withRequest($request)->withConfig($config);

        $listener = new class() extends CommandListener {
            protected static function buildDefinition(DefinitionBuilder $builder): DefinitionBuilder
            {
                return $builder->name('test')->subCommand('hello')->arg('name')->opt('caps');
            }

            protected function listenToCommand(Context $context, Input $input): void
            {
                $text = "Hello, {$input->get('name')}";
                $context->ack($input->get('caps') ? strtoupper($text) : $text);
            }
        };

        App::new()
            ->withLogger($this->logger)
            ->commandGroup('test', ['hello' => $listener])
            ->run($server);

        $result = $this->parseResponse($this->responseEmitter->response);
        $this->assertEquals('HELLO, JEREMY', $result['blocks'][0]['text']['text'] ?? null);
    }
}
