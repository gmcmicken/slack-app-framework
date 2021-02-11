<?php

declare(strict_types=1);

namespace Jeremeamia\Slack\Apps\Http;

use Jeremeamia\Slack\Apps\Auth\AuthException;
use Jeremeamia\Slack\Apps\Auth\AuthV0;
use Jeremeamia\Slack\Apps\Env;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    /** @var string */
    private $signingKey;

    /** @var int */
    private $maxClockSkew;

    public function __construct(string $signingKey, int $maxClockSkew)
    {
        $this->signingKey = $signingKey;
        $this->maxClockSkew = $maxClockSkew;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Authentication can be disabled via env var `SLACK_SKIP_AUTH=1` (for testing purposes only).
        if (Env::getSkipAuth()) {
            return $handler->handle($request);
        }

        $this->authenticateRequest($request);

        return $handler->handle($request);
    }

    /**
     * Authenticates the incoming request from Slack by verifying the signature.
     *
     * Currently, there is only one implementation: v0. In the future, there could be multiple. The Signature version
     * is included in the signature header, which is formatted like this: `X-Slack-Signature: {version}={signature}`
     *
     * @param ServerRequestInterface $request
     */
    private function authenticateRequest(ServerRequestInterface $request): void
    {
        if (!$request->hasHeader(AuthV0::HEADER_TIMESTAMP) || !$request->hasHeader(AuthV0::HEADER_SIGNATURE)) {
            throw new AuthException('Missing required headers');
        }

        $timestamp = (int) $request->getHeaderLine(AuthV0::HEADER_TIMESTAMP);
        AuthV0::validateTimestamp($timestamp, $this->maxClockSkew);

        $bodyContent = Util::readRequestBody($request);
        $signature = $request->getHeaderLine(AuthV0::HEADER_SIGNATURE);
        AuthV0::validateSignature($timestamp, $bodyContent, $signature, $this->signingKey);
    }
}
