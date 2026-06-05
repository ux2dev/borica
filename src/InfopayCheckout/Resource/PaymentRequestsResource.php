<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Http\JwsSigner;

final class PaymentRequestsResource
{
    public function __construct(
        private readonly CheckoutConfig $config,
        private readonly ApiTransport $transport,
        private readonly JwsSigner $jwsSigner,
    ) {}

    public function create(Session $session, PaymentRequestDto $request): ApiResponse
    {
        // The JWS is computed over the exact JSON the transport will send:
        // ApiTransport::encode() uses these same json_encode flags.
        $json = json_encode($request->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        $jws = $this->jwsSigner->sign(
            jsonBody: $json,
            privateKeyPem: $this->config->getPrivateKey(),
            certificatePem: $this->config->getCertificate(),
            passphrase: $this->config->getPrivateKeyPassphrase(),
        );

        return $this->transport->send($request, $this->config->baseUrl, [
            'Authorization' => $session->basicAuthHeader(),
            'X-JWS-Signature' => $jws,
        ]);
    }

    public function getStatus(Session $session, string $paymentRequestId): ApiResponse
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/v1/api/paymentRequests/' . rawurlencode($paymentRequestId) . '/status',
            headers: ['Authorization' => $session->basicAuthHeader()],
        );

        return $this->transport->wrap($response, PaymentStatus::class);
    }
}
