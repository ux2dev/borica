<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Resource;

use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Http\HttpTransport;

final class SessionsResource
{
    public function __construct(
        private readonly CheckoutConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    public function create(string $authId, string $authSecret): Session
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/v1/api/sessions',
            headers: [],
            body: [
                'authId' => $authId,
                'authSecret' => $authSecret,
            ],
        );

        return Session::fromArray($response);
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
