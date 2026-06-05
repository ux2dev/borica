<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use DateTimeInterface;
use Generator;
use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\MissingTransactionDates;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\Transaction;
use Ux2Dev\Borica\InfopayErp\Dto\TransactionsPage;

final class TransactionsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly ApiTransport $transport,
    ) {}

    /**
     * GET /api/accounts/{accountId}/transactions — returns a single page.
     * Follow `TransactionsPage::nextUrl()` manually or use `iterate()`.
     */
    public function list(
        Session $session,
        string $accountId,
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo,
    ): ApiResponse {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $this->buildListUrl($accountId, $dateFrom, $dateTo),
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, TransactionsPage::class);
    }

    /**
     * Iterator over every transaction in the [$dateFrom, $dateTo] window —
     * follows the HATEOAS `Links.Next.href` chain until exhausted.
     *
     * @return Generator<int, Transaction>
     */
    public function iterate(
        Session $session,
        string $accountId,
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo,
    ): Generator {
        $page = $this->fetchPage($session, $this->buildListUrl($accountId, $dateFrom, $dateTo));

        while (true) {
            foreach ($page->transactions?->booked ?? [] as $tx) {
                yield $tx;
            }

            $next = $page->nextUrl();
            if ($next === null || $next === '') {
                return;
            }

            $page = $this->fetchPage($session, $this->resolveUrl($next));
        }
    }

    /**
     * GET /api/accounts/{accountId}/transactionsMissingDates — reports
     * dates in [dateFrom, dateTo] where no transactions have been synced.
     */
    public function missingDates(
        Session $session,
        string $accountId,
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo,
    ): ApiResponse {
        $url = $this->config->baseUrl . '/api/accounts/' . rawurlencode($accountId) . '/transactionsMissingDates'
            . '?' . http_build_query([
                'dateFrom' => $dateFrom->format(DateTimeInterface::ATOM),
                'dateTo' => $dateTo->format(DateTimeInterface::ATOM),
            ]);

        $response = $this->transport->sendJson(
            method: 'GET',
            url: $url,
            headers: $session->authHeaders(),
        );

        return $this->transport->wrap($response, MissingTransactionDates::class);
    }

    private function buildListUrl(string $accountId, DateTimeInterface $dateFrom, DateTimeInterface $dateTo): string
    {
        return $this->config->baseUrl . '/api/accounts/' . rawurlencode($accountId) . '/transactions'
            . '?' . http_build_query([
                'dateFrom' => $dateFrom->format(DateTimeInterface::ATOM),
                'dateTo' => $dateTo->format(DateTimeInterface::ATOM),
            ]);
    }

    private function fetchPage(Session $session, string $url): TransactionsPage
    {
        $response = $this->transport->sendJson(
            method: 'GET',
            url: $url,
            headers: $session->authHeaders(),
        );

        return TransactionsPage::fromArray($response);
    }

    /**
     * The spec doesn't specify whether `Links.Next.href` is absolute or
     * path-relative. Handle both by prefixing with baseUrl when needed.
     */
    private function resolveUrl(string $href): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        return $this->config->baseUrl . '/' . ltrim($href, '/');
    }
}
