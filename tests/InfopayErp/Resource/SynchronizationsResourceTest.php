<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\SynchronizationsResource;
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
});

test('refresh POSTs AccountIds payload', function () {
    $client = new FakeHttpClient([FakeHttpClient::noContent()]);
    $resource = new SynchronizationsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $resource->refresh($this->session, ['acc-1', 'acc-2']);

    $req = $client->captured[0];
    expect($req->getMethod())->toBe('POST');
    expect((string) $req->getUri())->toBe('https://integration.infopay.bg/api/synchronizations/balancesAndTransactions/refresh');
    expect(json_decode((string) $req->getBody(), true))->toBe(['AccountIds' => ['acc-1', 'acc-2']]);
});

test('currentState GETs and parses sync state collection', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'States' => [
                [
                    'AccountId' => 'acc-1',
                    'IBAN' => 'BG80BNBG96611020345678',
                    'BalanceCurrentState' => ['State' => 'Success'],
                    'TransactioneCurrentState' => ['State' => 'Success'],
                ],
            ],
        ]),
    ]);
    $resource = new SynchronizationsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $state = $resource->currentState($this->session);

    expect($state->states)->toHaveCount(1);
    expect($state->states[0]->iban)->toBe('BG80BNBG96611020345678');
    expect($state->allSucceeded())->toBeTrue();
    expect($state->anyProcessing())->toBeFalse();
});

test('waitForSync polls until no account is Processing', function () {
    $processing = FakeHttpClient::json(200, [
        'States' => [[
            'AccountId' => 'acc-1', 'IBAN' => 'BG1',
            'BalanceCurrentState' => ['State' => 'Processing'],
            'TransactioneCurrentState' => ['State' => 'Processing'],
        ]],
    ]);
    $done = FakeHttpClient::json(200, [
        'States' => [[
            'AccountId' => 'acc-1', 'IBAN' => 'BG1',
            'BalanceCurrentState' => ['State' => 'Success'],
            'TransactioneCurrentState' => ['State' => 'Success'],
        ]],
    ]);
    $client = new FakeHttpClient([
        FakeHttpClient::noContent(),
        $processing,
        $done,
    ]);
    $resource = new SynchronizationsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $final = $resource->waitForSync(
        session: $this->session,
        accountIds: ['acc-1'],
        timeoutSeconds: 5,
        initialBackoffMs: 1,
        maxBackoffMs: 1,
    );

    expect($final->allSucceeded())->toBeTrue();
    expect($final->states[0]->balanceCurrentState?->state)->toBe(SyncCurrentState::Success);
    expect(count($client->captured))->toBe(3);
});

test('waitForSync throws on timeout when state stays Processing', function () {
    $processing = fn () => FakeHttpClient::json(200, [
        'States' => [[
            'AccountId' => 'a', 'IBAN' => 'BG1',
            'BalanceCurrentState' => ['State' => 'Processing'],
            'TransactioneCurrentState' => ['State' => 'Success'],
        ]],
    ]);
    $client = new FakeHttpClient([
        FakeHttpClient::noContent(),
        $processing(), $processing(), $processing(), $processing(), $processing(), $processing(),
    ]);
    $resource = new SynchronizationsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    expect(fn () => $resource->waitForSync(
        session: $this->session,
        timeoutSeconds: 0,
        initialBackoffMs: 1,
        maxBackoffMs: 1,
    ))->toThrow(RuntimeException::class);
});
