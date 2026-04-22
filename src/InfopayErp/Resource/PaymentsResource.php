<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentResult;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SingleSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class PaymentsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    /**
     * POST /api/payments/sepa-credit-transfers — submits one SEPA credit
     * transfer. Response includes ScaRedirect link for SCA completion.
     */
    public function createSepa(Session $session, SingleSepaPaymentRequest $request): PaymentResult
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/payments/sepa-credit-transfers',
            headers: $session->authHeaders(),
            body: $request->toArray(),
        );

        return PaymentResult::fromArray($response);
    }

    /** GET /api/payments/{paymentId}/status — polls for final status. */
    public function getStatus(Session $session, string $paymentId): PaymentStatus
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/api/payments/' . rawurlencode($paymentId) . '/status',
            headers: $session->authHeaders(),
        );

        return PaymentStatus::fromArray($response);
    }
}
