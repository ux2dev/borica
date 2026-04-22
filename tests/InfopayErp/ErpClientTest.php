<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\ErpClient;
use Ux2Dev\Borica\InfopayErp\Resource\AccountsResource;
use Ux2Dev\Borica\InfopayErp\Resource\BulkPaymentsResource;
use Ux2Dev\Borica\InfopayErp\Resource\InvoicesResource;
use Ux2Dev\Borica\InfopayErp\Resource\PaymentsResource;
use Ux2Dev\Borica\InfopayErp\Resource\SessionsResource;
use Ux2Dev\Borica\InfopayErp\Resource\SynchronizationsResource;
use Ux2Dev\Borica\InfopayErp\Resource\TransactionsResource;

test('ErpClient lazily exposes all 7 resources, memoizing each', function () {
    $config = new ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'unique-id',
        accessToken: 'access-token',
    );
    $factory = new HttpFactory();
    $client = new ErpClient($config, new Client(), $factory, $factory);

    expect($client->sessions())->toBeInstanceOf(SessionsResource::class);
    expect($client->synchronizations())->toBeInstanceOf(SynchronizationsResource::class);
    expect($client->accounts())->toBeInstanceOf(AccountsResource::class);
    expect($client->transactions())->toBeInstanceOf(TransactionsResource::class);
    expect($client->bulkPayments())->toBeInstanceOf(BulkPaymentsResource::class);
    expect($client->payments())->toBeInstanceOf(PaymentsResource::class);
    expect($client->invoices())->toBeInstanceOf(InvoicesResource::class);

    expect($client->sessions())->toBe($client->sessions());
    expect($client->payments())->toBe($client->payments());
});
