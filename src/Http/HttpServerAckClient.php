<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Contexts\AckClient;
use JsonSerializable;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

use function json_encode;
use function strlen;

class HttpServerAckClient implements AckClient
{
    /** @var ResponseInterface|null */
    private $response;

    public function ack(?JsonSerializable $message = null): void
    {
        if ($message) {
            $json = json_encode($message, JSON_THROW_ON_ERROR);
            $this->response = new Response(200, [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($json),
            ], $json);
        } else {
            $this->response = new Response(200);
        }
    }

    /**
     * @return ResponseInterface
     * @throw HttpException if no response.
     */
    public function getAckResponse(): ResponseInterface
    {
        if ($this->response === null) {
            throw new HttpException('Listener did not ack');
        }

        return $this->response;
    }
}
