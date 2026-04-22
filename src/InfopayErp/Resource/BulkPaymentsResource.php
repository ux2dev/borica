<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\BulkSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentResult;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class BulkPaymentsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    /**
     * POST /api/bulk-payments/sepa-credit-transfers — submits 2..250 SEPA
     * credit transfers in one batch. Response includes ScaRedirect link.
     */
    public function createSepa(Session $session, BulkSepaPaymentRequest $request): PaymentResult
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/bulk-payments/sepa-credit-transfers',
            headers: $session->authHeaders(),
            body: $request->toArray(),
        );

        return PaymentResult::fromArray($response);
    }

    /** GET /api/bulk-payments/{paymentId}/status — polls for final status. */
    public function getStatus(Session $session, string $paymentId): PaymentStatus
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/api/bulk-payments/' . rawurlencode($paymentId) . '/status',
            headers: $session->authHeaders(),
        );

        return PaymentStatus::fromArray($response);
    }
}
