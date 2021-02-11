<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Auth;

use Http\Client\Exception\HttpException;
use JsonException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

use function array_filter;
use function http_build_query;
use function json_decode;
use function strlen;

use const JSON_THROW_ON_ERROR;

class OAuthClient
{
    /** @var HttpClient */
    private $httpClient;

    public function __construct(?HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Psr18Client();
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $temporaryAccessCode
     * @param string|null $redirectUri
     * @return array Includes access_token, team.id, and enterprise.id fields
     * @throws ClientExceptionInterface
     */
    public function createAccessToken(
        string $clientId,
        string $clientSecret,
        string $temporaryAccessCode,
        ?string $redirectUri = null
    ): array {
        return $this->sendRequest('oauth.v2.access', [
            'code' => $temporaryAccessCode,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * @param string $accessToken
     * @param bool|null $test
     * @return array
     * @throws ClientExceptionInterface
     */
    public function revokeAccessToken(string $accessToken, ?bool $test = null): array
    {
        return $this->sendRequest('auth.revoke', [
            'token' => $accessToken,
            'test' => (int) $test,
        ]);
    }

    /**
     * @param string $api
     * @param array $input
     * @return array
     * @throws ClientExceptionInterface
     */
    private function sendRequest(string $api, array $input): array
    {
        $requestContent = http_build_query(array_filter($input));
        $request = new Request('POST', "https://slack.com/api/{$api}", [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Content-Length' => strlen($requestContent),
        ], $requestContent);
        $response = $this->httpClient->sendRequest($request);
        $responseContent = $response->getBody()->getContents();

        try {
            $result = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            throw new HttpException('Could not parse response body', $request, $response, $ex);
        }

        if (!isset($result['ok']) || !$result['ok']) {
            throw new HttpException('Request did not succeed', $request, $response);
        }

        return $result;
    }
}
