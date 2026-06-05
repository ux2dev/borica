<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\BulkSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayErp\Dto\Session;

final class BulkPaymentsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /**
     * POST /api/bulk-payments/sepa-credit-transfers — submits 2..250 SEPA
     * credit transfers in one batch. Response includes ScaRedirect link.
     */
    public function createSepa(Session $session, BulkSepaPaymentRequest $request): ApiResponse
    {
        return $this->transport->send($request, $this->config->baseUrl, $session->authHeaders());
    }

    /** GET /api/bulk-payments/{paymentId}/status — polls for final status. */
    public function getStatus(Session $session, string $paymentId): ApiResponse
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->config->baseUrl . '/api/bulk-payments/' . rawurlencode($paymentId) . '/status',
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, PaymentStatus::class);
    }
}
