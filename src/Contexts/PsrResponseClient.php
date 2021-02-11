<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Contexts;

use Jeremeamia\Slack\Apps\Exception;
use JsonSerializable;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientInterface as HttpClient;
use Throwable;

class PsrResponseClient implements ResponseClient
{
    /** @var HttpClient */
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function respond(string $responseUrl, JsonSerializable $message): void
    {
        try {
            $requestContent = json_encode($message, JSON_THROW_ON_ERROR);
            $request = new Request('POST', $responseUrl, [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($requestContent)
            ], $requestContent);

            $response = $this->httpClient->sendRequest($request);
            $responseContent = $response->getBody()->getContents();
            $ok = $this->getOK($responseContent);
        } catch (Throwable $exception) {
            throw new Exception('Response to message could not be completed', 0, $exception);
        }

        if (!$ok) {
            throw new Exception('Response to message failed');
        }
    }

    private function getOK(string $responseContent): bool
    {
        if ($responseContent === 'ok') {
            return true;
        }

        $data = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);

        return $data['ok'] ?? false;
    }
}
