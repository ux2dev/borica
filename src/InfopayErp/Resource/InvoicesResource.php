<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateRequest;
use Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateResult;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class InvoicesResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    /** POST /api/invoices — issue an invoice in InfoPay. */
    public function create(Session $session, InvoiceCreateRequest $request): InvoiceCreateResult
    {
        $response = $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/invoices',
            headers: $session->authHeaders(),
            body: $request->toArray(),
        );

        return InvoiceCreateResult::fromArray($response);
    }
}
