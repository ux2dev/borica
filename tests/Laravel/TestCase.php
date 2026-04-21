<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Tests\Laravel;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Ux2Dev\Borica\Laravel\BoricaServiceProvider;
use Ux2Dev\Borica\Laravel\Facades\Borica;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BoricaServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Borica' => Borica::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('borica.cgi.default', 'default');
        $app['config']->set('borica.cgi.merchants.default', [
            'terminal' => 'V1800001',
            'merchant_id' => 'MERCHANT01',
            'merchant_name' => 'Test Shop',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
            'private_key_passphrase' => null,
            'borica_public_key' => file_get_contents(__DIR__ . '/../fixtures/test_public_key.pem'),
            'environment' => 'development',
            'currency' => 'EUR',
            'country' => 'BG',
            'timezone_offset' => '+03',
        ]);
        $app['config']->set('borica.routes.enabled', true);
        $app['config']->set('borica.routes.prefix', 'borica');
        $app['config']->set('borica.routes.middleware', ['web']);
        $app['config']->set('borica.redirect.success', '/payment/success');
        $app['config']->set('borica.redirect.failure', '/payment/failure');
    }

    protected function getTestPrivateKey(): string
    {
        return file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    }

    protected function getTestPublicKey(): string
    {
        return file_get_contents(__DIR__ . '/../fixtures/test_public_key.pem');
    }

    protected function buildSignedCallbackData(array $overrides = []): array
    {
        $data = array_merge([
            'ACTION' => '0',
            'RC' => '00',
            'APPROVAL' => 'ABC123',
            'TERMINAL' => 'V1800001',
            'TRTYPE' => '1',
            'AMOUNT' => '9.00',
            'CURRENCY' => 'EUR',
            'ORDER' => '000001',
            'RRN' => '123456789012',
            'INT_REF' => 'INTREF123',
            'PARES_STATUS' => '',
            'ECI' => '',
            'TIMESTAMP' => gmdate('YmdHis'),
            'NONCE' => strtoupper(bin2hex(random_bytes(16))),
        ], $overrides);

        $macGeneral = new MacGeneral();
        $signer = new Signer();
        $signingData = $macGeneral->buildResponseSigningData($data);
        $data['P_SIGN'] = $signer->sign($signingData, $this->getTestPrivateKey());

        return $data;
    }
}
