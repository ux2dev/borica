<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\TransactionsResource;
use Ux2Dev\Borica\Tests\InfopayErp\FakeHttpClient;

require_once __DIR__ . '/../Helpers.php';

beforeEach(function () {
    $this->config = new ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'unique-id',
        accessToken: 'access-token',
    );
    $this->factory = new HttpFactory();
    $this->session = new Session('sess', SessionCreateStatus::Success, 'key');
    $this->dateFrom = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
    $this->dateTo = new DateTimeImmutable('2026-01-31T23:59:59+00:00');
});

test('list GETs transactions endpoint with date range', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'Account' => ['IBAN' => 'BG1'],
            'Balances' => [],
            'Transactions' => ['Booked' => []],
        ]),
    ]);
    $resource = new TransactionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $page = $resource->list($this->session, 'acc-1', $this->dateFrom, $this->dateTo);

    expect($page->account?->iban)->toBe('BG1');
    expect($page->transactions?->booked)->toBe([]);

    $uri = (string) $client->captured[0]->getUri();
    expect($uri)->toStartWith('https://integration.infopay.bg/api/accounts/acc-1/transactions');
    expect($uri)->toContain('dateFrom=');
    expect($uri)->toContain('dateTo=');
});

test('iterate follows Links.Next.href until exhausted', function () {
    $page1 = FakeHttpClient::json(200, [
        'Transactions' => [
            'Booked' => [
                ['TransactionAmount' => ['amount' => '10.00', 'currency' => 'EUR']],
                ['TransactionAmount' => ['amount' => '20.00', 'currency' => 'EUR']],
            ],
            'Links' => ['Next' => ['href' => 'https://integration.infopay.bg/api/accounts/acc-1/transactions?page=2']],
        ],
    ]);
    $page2 = FakeHttpClient::json(200, [
        'Transactions' => [
            'Booked' => [
                ['TransactionAmount' => ['amount' => '30.00', 'currency' => 'EUR']],
            ],
        ],
    ]);
    $client = new FakeHttpClient([$page1, $page2]);
    $resource = new TransactionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $amounts = [];
    foreach ($resource->iterate($this->session, 'acc-1', $this->dateFrom, $this->dateTo) as $tx) {
        $amounts[] = $tx->transactionAmount->amount;
    }

    expect($amounts)->toBe(['10.00', '20.00', '30.00']);
    expect((string) $client->captured[1]->getUri())->toBe('https://integration.infopay.bg/api/accounts/acc-1/transactions?page=2');
});

test('iterate resolves relative Next.href against baseUrl', function () {
    $page1 = FakeHttpClient::json(200, [
        'Transactions' => [
            'Booked' => [['TransactionAmount' => ['amount' => '1.00', 'currency' => 'EUR']]],
            'Links' => ['Next' => ['href' => '/api/accounts/acc-1/transactions?page=2']],
        ],
    ]);
    $page2 = FakeHttpClient::json(200, ['Transactions' => ['Booked' => []]]);
    $client = new FakeHttpClient([$page1, $page2]);
    $resource = new TransactionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    iterator_to_array($resource->iterate($this->session, 'acc-1', $this->dateFrom, $this->dateTo));

    expect((string) $client->captured[1]->getUri())->toBe('https://integration.infopay.bg/api/accounts/acc-1/transactions?page=2');
});

test('missingDates returns gap report', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'NotSyncedTransactionsDates' => ['2026-01-15T00:00:00Z', '2026-01-16T00:00:00Z'],
            'HasDatesNotSynced' => true,
            'Account' => ['IBAN' => 'BG1'],
        ]),
    ]);
    $resource = new TransactionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $report = $resource->missingDates($this->session, 'acc-1', $this->dateFrom, $this->dateTo);

    expect($report->hasDatesNotSynced)->toBeTrue();
    expect($report->notSyncedTransactionsDates)->toHaveCount(2);
});
