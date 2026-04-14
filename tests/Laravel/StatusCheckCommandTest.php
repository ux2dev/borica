<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

test('status-check command requires order argument', function () {
    $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
    $this->artisan('borica:status-check');
});

test('status-check command requires --type option', function () {
    $this->artisan('borica:status-check', ['order' => '000001'])
        ->expectsOutputToContain('--type option is required')
        ->assertFailed();
});

test('status-check command sends request and displays successful response', function () {
    $data = $this->buildSignedCallbackData([
        'ACTION' => '0',
        'RC' => '00',
        'ORDER' => '000001',
        'AMOUNT' => '12.00',
        'RRN' => '609801507100',
    ]);

    Http::fake([
        '*' => Http::response(http_build_query($data), 200),
    ]);

    $this->artisan('borica:status-check', [
        'order' => '000001',
        '--type' => 'purchase',
    ])
        ->expectsOutputToContain('SUCCESS')
        ->expectsOutputToContain('000001')
        ->assertSuccessful();
});

test('status-check command displays failed response', function () {
    $data = $this->buildSignedCallbackData([
        'ACTION' => '3',
        'RC' => '13',
    ]);

    Http::fake([
        '*' => Http::response(http_build_query($data), 200),
    ]);

    $this->artisan('borica:status-check', [
        'order' => '000001',
        '--type' => 'purchase',
    ])
        ->expectsOutputToContain('FAILED')
        ->assertSuccessful();
});

test('status-check accepts merchant option', function () {
    $data = $this->buildSignedCallbackData();

    Http::fake([
        '*' => Http::response(http_build_query($data), 200),
    ]);

    $this->artisan('borica:status-check', [
        'order' => '000001',
        '--type' => 'purchase',
        '--merchant' => 'default',
    ])
        ->assertSuccessful();
});

test('status-check validates transaction type', function () {
    $this->artisan('borica:status-check', [
        'order' => '000001',
        '--type' => 'invalid',
    ])
        ->expectsOutputToContain('Invalid transaction type')
        ->assertFailed();
});
