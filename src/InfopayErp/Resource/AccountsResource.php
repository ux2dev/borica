<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Account;
use Ux2Dev\Borica\InfopayErp\Dto\AccountCollection;
use Ux2Dev\Borica\InfopayErp\Dto\Session;

final class AccountsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /** GET /api/accounts — list every account linked to the ERP registration. */
    public function list(Session $session, bool $withBalance = false): ApiResponse
    {
        $url = $this->config->baseUrl . '/api/accounts';
        if ($withBalance) {
            $url .= '?withBalance=true';
        }

        $response = $this->transport->sendJson(
            method: 'GET',
            url: $url,
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, AccountCollection::class);
    }

    /** GET /api/accounts/{accountId} — fetch a single account by ID. */
    public function get(Session $session, string $accountId, bool $withBalance = false): ApiResponse
    {
        $url = $this->config->baseUrl . '/api/accounts/' . rawurlencode($accountId);
        if ($withBalance) {
            $url .= '?withBalance=true';
        }

        $response = $this->transport->sendJson(
            method: 'GET',
            url: $url,
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, Account::class);
    }
}
