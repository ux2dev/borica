<?php

declare(strict_types=1);

use Ux2Dev\Borica\Config\BoricaConfig;
use Ux2Dev\Borica\Exception\ConfigurationException;

test('exposes configured services and rejects unconfigured ones', function () {
    $config = new BoricaConfig(cgi: test_merchant_config());

    expect($config->cgi)->not->toBeNull();
    expect($config->requireCgi())->toBe($config->cgi);
    expect(fn () => $config->requireCheckout())->toThrow(ConfigurationException::class);
    expect(fn () => $config->requireErp())->toThrow(ConfigurationException::class);
});
