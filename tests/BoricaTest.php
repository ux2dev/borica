<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Cgi\CgiArea;
use Ux2Dev\Borica\Config\BoricaConfig;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayCheckout\CheckoutArea;
use Ux2Dev\Borica\InfopayErp\ErpArea;

test('exposes configured service areas, lazily and cached', function () {
    $f = new HttpFactory();
    $borica = new Borica(
        new BoricaConfig(cgi: test_merchant_config(), checkout: test_checkout_config(), erp: test_erp_config()),
        test_psr18_client(), $f, $f,
    );

    expect($borica->cgi())->toBeInstanceOf(CgiArea::class);
    expect($borica->cgi())->toBe($borica->cgi());
    expect($borica->checkout())->toBeInstanceOf(CheckoutArea::class);
    expect($borica->erp())->toBeInstanceOf(ErpArea::class);
});

test('throws when accessing an unconfigured area', function () {
    $f = new HttpFactory();
    $borica = new Borica(new BoricaConfig(cgi: test_merchant_config()), test_psr18_client(), $f, $f);
    expect(fn () => $borica->erp())->toThrow(ConfigurationException::class);
});
