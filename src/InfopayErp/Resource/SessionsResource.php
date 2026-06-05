<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SessionCheckResult;
use Ux2Dev\Borica\InfopayErp\Dto\SessionCreateRequest;

final class SessionsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /**
     * POST /api/session. Exchanges the merchant's `uniqueId` + `accessToken`
     * for a session used on all subsequent calls. The Session is reached via
     * the returned envelope's first().
     */
    public function create(): ApiResponse
    {
        $request = new SessionCreateRequest(
            uniqueId: $this->config->uniqueId,
            accessToken: $this->config->accessToken,
        );

        return $this->transport->send($request, $this->config->baseUrl);
    }

    /** POST /api/session/check — returns the current session's liveness state. */
    public function check(Session $session): ApiResponse
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/session/check',
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, SessionCheckResult::class);
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
