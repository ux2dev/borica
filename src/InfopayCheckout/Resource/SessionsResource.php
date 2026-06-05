<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\CreateSessionRequest;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionStatusCode;

final class SessionsResource
{
    public function __construct(
        private readonly CheckoutConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    public function create(CreateSessionRequest $request): ApiResponse
    {
        return $this->transport->send($request, $this->config->baseUrl);
    }

    public function close(Session $session): void
    {
        $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/v1/api/sessions/close',
            headers: ['Authorization' => $session->basicAuthHeader()],
        );
    }

    public function check(Session $session): SessionStatusCode
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/v1/api/sessions/check',
            headers: ['Authorization' => $session->basicAuthHeader()],
        );

        return SessionStatusCode::from($response['sessionStatus']);
    }
}
