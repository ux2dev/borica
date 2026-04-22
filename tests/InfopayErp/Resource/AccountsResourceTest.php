<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Enum\AccountType;
use Ux2Dev\Borica\InfopayErp\Enum\BalanceType;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\AccountsResource;
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

test('list returns AccountCollection without ?withBalance by default', function () {
    $client = new FakeHttpClient([FakeHttpClient::json(200, ['Accounts' => []])]);
    $resource = new AccountsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $resource->list($this->session);

    expect((string) $client->captured[0]->getUri())->toBe('https://integration.infopay.bg/api/accounts');
});

test('list?withBalance=true includes the query flag', function () {
    $client = new FakeHttpClient([FakeHttpClient::json(200, ['Accounts' => []])]);
    $resource = new AccountsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $resource->list($this->session, withBalance: true);

    expect((string) $client->captured[0]->getUri())->toBe('https://integration.infopay.bg/api/accounts?withBalance=true');
});

test('get returns parsed Account with balances', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'AccountId' => 'acc-1',
            'IBAN' => 'BG1',
            'Currency' => 'EUR',
            'Type' => 'Current',
            'Balances' => [[
                'BalanceAmount' => ['amount' => '1234.56', 'currency' => 'EUR'],
                'BalanceType' => 'ActualBalance',
            ]],
        ]),
    ]);
    $resource = new AccountsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $account = $resource->get($this->session, 'acc-1');

    expect($account->accountId)->toBe('acc-1');
    expect($account->type)->toBe(AccountType::Current);
    expect($account->balances)->toHaveCount(1);
    expect($account->balances[0]->balanceType)->toBe(BalanceType::ActualBalance);
    expect($account->balances[0]->balanceAmount->amount)->toBe('1234.56');
});
