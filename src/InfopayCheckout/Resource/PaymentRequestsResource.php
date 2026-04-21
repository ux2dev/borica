<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Resource;

use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestResult;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Http\HttpTransport;
use Ux2Dev\Borica\InfopayCheckout\Http\JwsSigner;

final class PaymentRequestsResource
{
    public function __construct(
        private readonly CheckoutConfig $config,
        private readonly HttpTransport $transport,
        private readonly JwsSigner $jwsSigner,
    ) {}

    public function create(Session $session, PaymentRequestDto $request): PaymentRequestResult
    {
        $body = $request->toArray();
        $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        $jws = $this->jwsSigner->sign(
            jsonBody: $json,
            privateKeyPem: $this->config->getPrivateKey(),
            certificatePem: $this->config->getCertificate(),
            passphrase: $this->config->getPrivateKeyPassphrase(),
        );

        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/v1/api/paymentRequests',
            headers: [
                'Authorization' => $session->basicAuthHeader(),
                'X-JWS-Signature' => $jws,
            ],
            body: $body,
        );

        return PaymentRequestResult::fromArray($response);
    }

    public function getStatus(Session $session, string $paymentRequestId): PaymentStatus
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/v1/api/paymentRequests/' . rawurlencode($paymentRequestId) . '/status',
            headers: ['Authorization' => $session->basicAuthHeader()],
        );

        return PaymentStatus::fromArray($response);
    }
}
