<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Account;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Resource\AccountsResource;
use Ux2Dev\Borica\InfopayErp\Resource\SessionsResource;
use Ux2Dev\Borica\Tests\InfopayErp\FakeHttpClient;

require_once __DIR__ . '/Helpers.php';

test('create a session, then use it to fetch an account, over ApiTransport', function () {
    $config = new ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'unique-id',
        accessToken: 'access-token',
    );
    $factory = new HttpFactory();

    $sessionClient = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'SessionId' => 'sess-1',
            'SessionKey' => 'sess-key-32-chars-or-more-here-x',
            'Status' => 'Success',
        ]),
    ]);
    $sessionResp = (new SessionsResource($config, new ApiTransport($sessionClient, $factory, $factory)))->create();

    expect($sessionResp)->toBeInstanceOf(ApiResponse::class);
    $session = $sessionResp->first();
    expect($session)->toBeInstanceOf(Session::class);

    $accountClient = new FakeHttpClient([
        FakeHttpClient::json(200, ['AccountId' => 'acc-1', 'IBAN' => 'BG1', 'Currency' => 'EUR', 'Type' => 'Current']),
    ]);
    $accountResp = (new AccountsResource($config, new ApiTransport($accountClient, $factory, $factory)))->get($session, 'acc-1');

    expect($accountResp)->toBeInstanceOf(ApiResponse::class);
    expect($accountResp->first())->toBeInstanceOf(Account::class);
    expect($accountResp->first()->accountId)->toBe('acc-1');
    expect($accountClient->captured[0]->getHeaderLine('SessionId'))->toBe('sess-1');
});
