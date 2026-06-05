<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');
uses(Ux2Dev\Borica\Tests\Laravel\TestCase::class)->in('Laravel');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Shared fixtures for the redesigned single-client / tenant API
|--------------------------------------------------------------------------
*/

function test_private_key(): string
{
    return (string) file_get_contents(__DIR__ . '/fixtures/test_private_key.pem');
}

function test_merchant_config(): \Ux2Dev\Borica\Config\CgiConfig
{
    return new \Ux2Dev\Borica\Config\CgiConfig(
        terminal: 'V1800001',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: test_private_key(),
        environment: \Ux2Dev\Borica\Enum\Environment::Development,
        currency: \Ux2Dev\Borica\Enum\Currency::EUR,
        country: 'BG',
        timezoneOffset: '+03',
    );
}

function test_checkout_config(): \Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig
{
    return new \Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg',
        authId: 'auth-id',
        authSecret: 'auth-secret',
        shopId: 'shop-1',
        privateKey: test_private_key(),
        certificate: (string) file_get_contents(__DIR__ . '/fixtures/test_certificate.pem'),
    );
}

function test_erp_config(): \Ux2Dev\Borica\InfopayErp\Config\ErpConfig
{
    return new \Ux2Dev\Borica\InfopayErp\Config\ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'unique-id',
        accessToken: 'access-token',
    );
}

/** A do-nothing PSR-18 client; areas only build it lazily, no calls are made. */
function test_psr18_client(): \Psr\Http\Client\ClientInterface
{
    return new class implements \Psr\Http\Client\ClientInterface {
        public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            return new \GuzzleHttp\Psr7\Response(200, [], '{}');
        }
    };
}

/** A CGI tenant config array with an inline PEM key (for manager tests). */
function test_cgi_config_array(): array
{
    return [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'Test Shop',
        'environment' => 'development',
        'currency' => 'EUR',
        'private_key' => test_private_key(),
    ];
}

function test_cgi_area(): \Ux2Dev\Borica\Cgi\CgiArea
{
    return new \Ux2Dev\Borica\Cgi\CgiArea(test_merchant_config());
}
