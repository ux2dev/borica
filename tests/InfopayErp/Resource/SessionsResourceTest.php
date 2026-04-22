<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Enum\SessionState;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\SessionsResource;
use Ux2Dev\Borica\Tests\InfopayErp\FakeHttpClient;

require_once __DIR__ . '/../Helpers.php';

beforeEach(function () {
    $this->config = new ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'a78941c2-3fab-428f-b614-1422b42a0e46',
        accessToken: 'test-access-token',
    );
    $this->factory = new HttpFactory();
});

test('create POSTs uniqueId/accessToken and returns Session with auth headers', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'SessionId' => 'sess-1',
            'SessionKey' => 'sess-key-32-chars-or-more-here-x',
            'Status' => 'Success',
        ]),
    ]);
    $resource = new SessionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $session = $resource->create();

    expect($session->sessionId)->toBe('sess-1');
    expect($session->status)->toBe(SessionCreateStatus::Success);
    expect($session->authHeaders())->toBe([
        'SessionId' => 'sess-1',
        'SessionKey' => 'sess-key-32-chars-or-more-here-x',
    ]);

    $req = $client->captured[0];
    expect($req->getMethod())->toBe('POST');
    expect((string) $req->getUri())->toBe('https://integration.infopay.bg/api/session');
    expect(json_decode((string) $req->getBody(), true))->toBe([
        'uniqueId' => 'a78941c2-3fab-428f-b614-1422b42a0e46',
        'accessToken' => 'test-access-token',
    ]);
});

test('Status InvaliCredentials maps to enum case (spec typo preserved)', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, ['SessionId' => '', 'Status' => 'InvaliCredentials']),
    ]);
    $resource = new SessionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $session = $resource->create();

    expect($session->status)->toBe(SessionCreateStatus::InvaliCredentials);
    expect($session->authHeaders())->toBe([]);
});

test('check POSTs to /api/session/check with SessionId/SessionKey headers', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, ['State' => 'Valid']),
    ]);
    $resource = new SessionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $session = new Session('sess-1', SessionCreateStatus::Success, 'sess-key');
    $result = $resource->check($session);

    expect($result->state)->toBe(SessionState::Valid);

    $req = $client->captured[0];
    expect((string) $req->getUri())->toBe('https://integration.infopay.bg/api/session/check');
    expect($req->getHeaderLine('SessionId'))->toBe('sess-1');
    expect($req->getHeaderLine('SessionKey'))->toBe('sess-key');
});

test('close POSTs to /api/session/close', function () {
    $client = new FakeHttpClient([FakeHttpClient::noContent()]);
    $resource = new SessionsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $resource->close(new Session('sess-1', SessionCreateStatus::Success, 'sess-key'));

    expect((string) $client->captured[0]->getUri())->toBe('https://integration.infopay.bg/api/session/close');
});
