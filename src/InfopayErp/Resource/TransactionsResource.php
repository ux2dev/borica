<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Resource;

use DateTimeInterface;
use Generator;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\MissingTransactionDates;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\Transaction;
use Ux2Dev\Borica\InfopayErp\Dto\TransactionsPage;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;

final class TransactionsResource
{
    public function __construct(
        private readonly ErpConfig $config,
        private readonly HttpTransport $transport,
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
    ): TransactionsPage {
        $url = $this->config->baseUrl . '/api/accounts/' . rawurlencode($accountId) . '/transactions'
            . '?' . http_build_query([
                'dateFrom' => $dateFrom->format(DateTimeInterface::ATOM),
                'dateTo' => $dateTo->format(DateTimeInterface::ATOM),
            ]);

        return $this->fetchPage($session, $url);
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
        $page = $this->list($session, $accountId, $dateFrom, $dateTo);

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
    ): MissingTransactionDates {
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

        return MissingTransactionDates::fromArray($response);
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
