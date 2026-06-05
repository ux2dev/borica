<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateRequest;
use Ux2Dev\Borica\InfopayErp\Dto\Session;

final class InvoicesResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /** POST /api/invoices — issue an invoice in InfoPay. */
    public function create(Session $session, InvoiceCreateRequest $request): ApiResponse
    {
        return $this->transport->send($request, $this->config->baseUrl, $session->authHeaders());
    }
}
