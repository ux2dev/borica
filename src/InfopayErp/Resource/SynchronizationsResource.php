<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\AccountSyncStateCollection;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SyncRefreshRequest;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class SynchronizationsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
    ) {}

    /**
     * POST /api/synchronizations/balancesAndTransactions/refresh — triggers
     * an async refresh. Returns immediately with 204; poll currentState()
     * to determine completion.
     *
     * @param array<int, string> $accountIds Leave empty to refresh all accounts
     */
    public function refresh(Session $session, array $accountIds = []): void
    {
        $request = new SyncRefreshRequest(accountIds: $accountIds);

        $this->transport->sendJson(
            method: 'POST',
            url: $this->config->baseUrl . '/api/synchronizations/balancesAndTransactions/refresh',
            headers: $session->authHeaders(),
            body: $request->toArray(),
        );
    }

    /**
     * GET /api/synchronizations/balancesAndTransactions/currentState —
     * returns the current sync state for the given accounts (or all).
     *
     * @param array<int, string> $accountIds
     */
    public function currentState(Session $session, array $accountIds = []): AccountSyncStateCollection
    {
        $url = $this->config->baseUrl . '/api/synchronizations/balancesAndTransactions/currentState';
        if ($accountIds !== []) {
            $url .= '?' . http_build_query(['accountIds' => $accountIds]);
        }

        $response = $this->transport->sendJson(
            method: 'GET',
            url: $url,
            headers: $session->authHeaders(),
        );

        return AccountSyncStateCollection::fromArray($response);
    }
}
