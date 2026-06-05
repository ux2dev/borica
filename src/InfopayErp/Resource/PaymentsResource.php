<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SingleSepaPaymentRequest;

final class PaymentsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /**
     * POST /api/payments/sepa-credit-transfers — submits one SEPA credit
     * transfer. Response includes ScaRedirect link for SCA completion.
     */
    public function createSepa(Session $session, SingleSepaPaymentRequest $request): ApiResponse
    {
        return $this->transport->send($request, $this->config->baseUrl, $session->authHeaders());
    }

    /** GET /api/payments/{paymentId}/status — polls for final status. */
    public function getStatus(Session $session, string $paymentId): ApiResponse
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/api/payments/' . rawurlencode($paymentId) . '/status',
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, PaymentStatus::class);
    }
}
