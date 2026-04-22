<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SessionCheckResult;
use Ux2Dev\Borica\InfopayErp\Dto\SessionCreateRequest;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class SessionsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    /**
     * POST /api/session. Exchanges the merchant's `uniqueId` + `accessToken`
     * for a session used on all subsequent calls.
     */
    public function create(): Session
    {
        $request = new SessionCreateRequest(
            uniqueId: $this->config->uniqueId,
            accessToken: $this->config->accessToken,
        );

        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/session',
            headers: [],
            body: $request->toArray(),
        );

        return Session::fromArray($response);
    }

    /** POST /api/session/check — returns the current session's liveness state. */
    public function check(Session $session): SessionCheckResult
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/session/check',
            headers: $session->authHeaders(),
        );

        return SessionCheckResult::fromArray($response);
    }

    /** POST /api/session/close — terminates the current session. */
    public function close(Session $session): void
    {
        $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/session/close',
            headers: $session->authHeaders(),
        );
    }
}
