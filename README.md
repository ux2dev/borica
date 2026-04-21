# ux2dev/borica

PHP library for the BORICA eCommerce CGI payment gateway. Handles request signing, response verification, and all six transaction types defined by the BORICA protocol.

## Requirements

- PHP 8.1 or higher
- OpenSSL extension (`ext-openssl`)
- A BORICA merchant account with:
  - Terminal ID (8 alphanumeric characters)
  - Merchant ID
  - RSA private key in PEM format (provided by BORICA or generated per their instructions)

## Installation

```bash
composer require ux2dev/borica
```

## Migrating from v0.2.x to v0.3.x

v0.3 restructures the library around per-service resource-based clients to
accommodate additional BORICA services (Infopay Checkout is next).

**Namespace change:** `Ux2Dev\Borica\Borica` is replaced by
`Ux2Dev\Borica\Cgi\CgiClient`. Request and Response classes moved from
`Ux2Dev\Borica\Request` / `Ux2Dev\Borica\Response` to
`Ux2Dev\Borica\Cgi\Request` / `Ux2Dev\Borica\Cgi\Response`.

**Method mapping:**

| v0.2                                      | v0.3                                    |
|-------------------------------------------|-----------------------------------------|
| `$borica->createPaymentRequest()`         | `$cgi->payments()->purchase()`          |
| `$borica->createReversalRequest()`        | `$cgi->payments()->reverse()`           |
| `$borica->createPreAuthRequest()`         | `$cgi->preAuth()->create()`             |
| `$borica->createPreAuthCompleteRequest()` | `$cgi->preAuth()->complete()`           |
| `$borica->createPreAuthReversalRequest()` | `$cgi->preAuth()->reverse()`            |
| `$borica->createStatusCheckRequest()`     | `$cgi->status()->check()`               |
| `$borica->parseResponse()`                | `$cgi->responses()->parse()`            |

**Laravel config:** wrap the existing `default` + `merchants` block under a top-level `cgi` key:

```php
return [
    'cgi' => [
        'default' => env('BORICA_MERCHANT', 'default'),
        'merchants' => [ /* existing entries unchanged */ ],
    ],
    // routes, redirect unchanged
];
```

**Facade:** `Borica::createPaymentRequest(...)` becomes either
`Borica::payments()->purchase(...)` (shorthand via `__call` proxy to the default CGI merchant) or
`Borica::cgi()->payments()->purchase(...)` (explicit).

**Request DTOs:** Constructor properties on `PaymentRequest`, `ReversalRequest`, `PreAuthRequest`, `PreAuthCompleteRequest`, `PreAuthReversalRequest`, and `StatusCheckRequest` remain `private`. Read values via `->toArray()`, `->getTransactionType()`, and `->getSigningFields()`.

## Configuration

Create a `MerchantConfig` instance with your merchant credentials:

```php
use Ux2Dev\Borica\Cgi\CgiClient;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;

$config = new MerchantConfig(
    terminal: 'V1800001',
    merchantId: '1600000001',
    merchantName: 'My Shop',
    privateKey: file_get_contents('/path/to/private_key.pem'),
    environment: Environment::Development,  // or Environment::Production
    currency: Currency::EUR,                // BGN, EUR, or USD
    country: 'BG',                          // default: 'BG'
    timezoneOffset: '+03',                  // default: '+03'
    privateKeyPassphrase: 'secret',         // optional, if key is encrypted
);

$cgi = new CgiClient($config);
```

The config validates all inputs on construction. The private key and passphrase are never exposed through public properties or serialization.

### PSR-3 Logging

Pass any PSR-3 logger as the second argument:

```php
$cgi = new CgiClient($config, $logger);
```

### Gateway URLs

The gateway URL is determined by the environment:

| Environment   | URL                                                  |
|---------------|------------------------------------------------------|
| Development   | `https://3dsgate-dev.borica.bg/cgi-bin/cgi_link`     |
| Production    | `https://3dsgate.borica.bg/cgi-bin/cgi_link`         |

```php
$gatewayUrl = $cgi->getGatewayUrl();
```

## Usage

### Payment (Transaction Type 1)

Browser-based payment. Build the request, then POST the form data to the gateway URL.

```php
$request = $cgi->payments()->purchase(
    amount: '49.99',
    order: '000001',
    description: 'Order #000001',
    mInfo: [],
);

// Build an auto-submitting HTML form
$gatewayUrl = $cgi->getGatewayUrl();
$formFields = $request->toArray();
```

Render the form:

```html
<form id="borica" method="POST" action="<?= $gatewayUrl ?>">
    <?php foreach ($formFields as $name => $value): ?>
        <input type="hidden" name="<?= $name ?>" value="<?= htmlspecialchars($value) ?>">
    <?php endforeach; ?>
    <button type="submit">Pay</button>
</form>
```

#### Optional parameters

```php
$request = $cgi->payments()->purchase(
    amount: '49.99',
    order: '000001',
    description: 'Order #000001',
    mInfo: ['cardholderName' => 'John'],   // additional merchant info (base64-encoded JSON)
    adCustBorOrderId: 'MY-SHOP-1234',      // custom order ID shown to customer
    language: 'EN',                         // form language (default: 'BG')
    email: 'customer@example.com',          // customer email
    merchantUrl: 'https://shop.com/notify', // notification URL (must be HTTPS)
);
```

### Pre-Authorization (Transaction Type 12)

Reserves an amount on the customer's card without capturing it. Same interface as payment.

```php
$request = $cgi->preAuth()->create(
    amount: '100.00',
    order: '000002',
    description: 'Pre-auth for booking #000002',
    mInfo: [],
);

$formFields = $request->toArray();
// POST to $cgi->getGatewayUrl()
```

### Complete Pre-Authorization (Transaction Type 21)

Captures a previously pre-authorized amount. Server-to-server -- POST directly to the gateway.

```php
$request = $cgi->preAuth()->complete(
    amount: '100.00',
    order: '000002',
    rrn: $preAuthResponse->getRrn(),       // RRN from the pre-auth response
    intRef: $preAuthResponse->getIntRef(), // INT_REF from the pre-auth response
    description: 'Capture booking #000002',
);

// POST $request->toArray() to $cgi->getGatewayUrl() via HTTP client
```

### Reverse Pre-Authorization (Transaction Type 22)

Releases a pre-authorized hold.

```php
$request = $cgi->preAuth()->reverse(
    amount: '100.00',
    order: '000002',
    rrn: $preAuthResponse->getRrn(),
    intRef: $preAuthResponse->getIntRef(),
    description: 'Cancel booking #000002',
);
```

### Reversal (Transaction Type 24)

Reverses a completed payment.

```php
$request = $cgi->payments()->reverse(
    amount: '49.99',
    order: '000001',
    rrn: $paymentResponse->getRrn(),
    intRef: $paymentResponse->getIntRef(),
    description: 'Refund order #000001',
);
```

### Status Check (Transaction Type 90)

Query the status of a previous transaction. Server-to-server.

```php
use Ux2Dev\Borica\Enum\TransactionType;

$request = $cgi->status()->check(
    order: '000001',
    transactionType: TransactionType::Purchase, // type of the original transaction
);

// POST $request->toArray() to $cgi->getGatewayUrl() via HTTP client
```

### Parsing the Gateway Response

When BORICA redirects back to your site (for browser-based transactions) or returns an HTTP response (for server-to-server transactions), parse and verify it:

```php
// $data is the associative array from the gateway (e.g. $_POST for callbacks)
$response = $cgi->responses()->parse($data, TransactionType::Purchase);

if ($response->isSuccessful()) {
    $approval = $response->getApproval();
    $rrn = $response->getRrn();
    $intRef = $response->getIntRef();
    // Mark order as paid
} else {
    $error = $response->getErrorMessage();
    // Handle failure
}
```

The library automatically verifies the `P_SIGN` signature using the BORICA public key for the configured environment. An `InvalidResponseException` is thrown if the signature is missing or invalid.

### Response Object

The `Response` object provides getters for all gateway fields:

| Method                | Returns    | Description                        |
|-----------------------|------------|------------------------------------|
| `isSuccessful()`      | `bool`     | `true` when ACTION=0 and RC=00     |
| `getAction()`         | `string`   | Response action code               |
| `getRc()`             | `string`   | Response code                      |
| `getApproval()`       | `?string`  | Authorization code                 |
| `getTerminal()`       | `string`   | Terminal ID                        |
| `getTrtype()`         | `string`   | Transaction type                   |
| `getAmount()`         | `?string`  | Transaction amount                 |
| `getCurrency()`       | `?string`  | Currency code                      |
| `getOrder()`          | `string`   | Order number                       |
| `getRrn()`            | `?string`  | Retrieval reference number         |
| `getIntRef()`         | `?string`  | Internal reference                 |
| `getCard()`           | `?string`  | Masked card number                 |
| `getCardBrand()`      | `?string`  | Card brand (Visa, MC, etc.)        |
| `getEci()`            | `?string`  | ECI indicator                      |
| `getParesStatus()`    | `?string`  | 3DS authentication result          |
| `getTimestamp()`      | `string`   | Response timestamp (YmdHis, UTC)   |
| `getNonce()`          | `string`   | Response nonce                     |
| `getErrorMessage()`   | `string`   | Human-readable error description   |
| `getStatusMessage()`  | `?string`  | Gateway status message             |
| `getCardholderInfo()` | `?string`  | Cardholder information             |

## Input Validation

The library validates all inputs before signing:

| Parameter   | Rule                                                    |
|-------------|---------------------------------------------------------|
| amount      | Positive number, exactly 2 decimal places (e.g. `9.00`) |
| order       | 1-15 digits                                             |
| description | 1-125 characters, non-empty                             |
| email       | Valid email format (when provided)                      |
| merchantUrl | Valid HTTPS URL (when provided)                         |
| nonce       | 32 uppercase hex characters (auto-generated if omitted) |
| timestamp   | 14 digits, YmdHis format (auto-generated if omitted)    |
| mInfo       | Encoded size must not exceed 2048 bytes                  |
| terminal    | Exactly 8 alphanumeric characters                       |

A `ConfigurationException` is thrown when validation fails.

## Error Handling

The library defines specific exception types:

| Exception                    | When                                              |
|------------------------------|---------------------------------------------------|
| `ConfigurationException`     | Invalid merchant config or request parameters     |
| `SigningException`           | Private/public key loading or signing failure     |
| `InvalidResponseException`   | Missing or invalid P_SIGN in gateway response     |

All exceptions extend `BoricaException`, so you can catch broadly or narrowly:

```php
use Ux2Dev\Borica\Exception\BoricaException;
use Ux2Dev\Borica\Exception\InvalidResponseException;

try {
    $response = $cgi->responses()->parse($data, TransactionType::Purchase);
} catch (InvalidResponseException $e) {
    // Signature verification failed -- do not trust this response
    log($e->getMessage());
    log($e->getResponseData()); // sensitive fields are redacted
} catch (BoricaException $e) {
    // Any other library error
}
```

## Infopay Checkout

BORICA's Infopay Checkout is a REST API for bank-transfer payments (domestic credit transfers, budget transfers, SEPA). It is a separate service from the CGI card-payment gateway and uses its own credentials, private key, and base URL.

### Standalone usage

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayCheckout\CheckoutClient;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\Account;
use Ux2Dev\Borica\InfopayCheckout\Dto\DomesticCreditTransferBgn;
use Ux2Dev\Borica\InfopayCheckout\Dto\InstructedAmount;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentLanguage;

$config = new CheckoutConfig(
    baseUrl: 'https://uat-api-checkout.infopay.bg',
    authId: 'your-auth-id',
    authSecret: 'your-auth-secret',
    shopId: '69e1dbb5-1d28-4059-a5a4-b1b56b84a86d',
    privateKey: file_get_contents('/path/to/checkout-private.key'),
);

$factory = new HttpFactory();
$client = new CheckoutClient(
    config: $config,
    httpClient: new Client(),
    requestFactory: $factory,
    streamFactory: $factory,
);

// 1. Log in to obtain a session
$session = $client->sessions()->create($config->authId, $config->authSecret);

// 2. Create a payment request
$payment = $client->paymentRequests()->create(
    session: $session,
    request: new PaymentRequestDto(
        shopId: $config->shopId,
        beneficiaryDefaultAccount: new Account('BG29RZBB91550123456789'),
        instructedAmount: new InstructedAmount(150.00, InstructedAmountCurrency::Bgn),
        details: 'Order No 5679',
        validTime: new DateTimeImmutable('+1 day'),
        externalReferenceId: bin2hex(random_bytes(16)),
        paymentDetails: new DomesticCreditTransferBgn('Pay Invoice 123'),
        successUrl: 'https://merchant.com/success',
        errorUrl: 'https://merchant.com/error',
        language: PaymentLanguage::Bg,
    ),
);

// 3. Redirect the customer to the checkout URL
header('Location: ' . $payment->checkoutUrl);
exit;

// 4. Poll for status (or wait for BORICA callback)
$status = $client->paymentRequests()->getStatus($session, $payment->paymentRequestId);

// 5. Close the session when done
$client->sessions()->close($session);
```

### Laravel usage

After adding the `checkout` config block (see Configuration), use the facade:

```php
use Ux2Dev\Borica\Laravel\Facades\Borica;

$checkout = Borica::checkout();

$session = $checkout->sessions()->create(
    config('borica.checkout.merchants.default.auth_id'),
    config('borica.checkout.merchants.default.auth_secret'),
);

$payment = $checkout->paymentRequests()->create($session, $paymentDto);
```

### Supported payment types

- `DomesticCreditTransferBgn` - domestic BGN credit transfer
- `DomesticBudgetTransferBgn` - budget transfer (requires `ultimateDebtor` + `BudgetPaymentDetails`)
- `SepaCreditTransfer` - SEPA credit transfer

All three extend `PaymentDetails` and can be passed into `PaymentRequestDto::paymentDetails`.

### HTTP client

The package depends on `psr/http-client` and `psr/http-factory` interfaces only. You can inject any PSR-18 client (Guzzle, Symfony HTTP Client, `kriswallsmith/buzz`, etc). If you don't already have one, `composer require guzzlehttp/guzzle` provides both the client and a PSR-17 factory out of the box.

### JWS signing

`POST /v1/api/paymentRequests` requires an `X-JWS-Signature` header over the request body. The library signs the JSON body with the configured private key using RS256 detached JWS (RFC 7515 + RFC 7797's `b64=false` header). BORICA issues a separate keypair for the Checkout service - do not reuse the CGI signing key.

## Laravel Integration

The library includes a Laravel integration layer that works with Laravel 10, 11, 12, and 13. The core library remains framework-agnostic -- the Laravel code lives entirely in `src/Laravel/`.

### Setup

The package auto-discovers via `extra.laravel.providers` in `composer.json`. No manual registration needed.

Publish the config file:

```bash
php artisan vendor:publish --tag=borica-config
```

Add your merchant credentials to `.env`:

```env
BORICA_TERMINAL=V1800001
BORICA_MERCHANT_ID=1600000001
BORICA_MERCHANT_NAME="My Shop"
BORICA_PRIVATE_KEY=/path/to/private.key
BORICA_ENVIRONMENT=development
BORICA_CURRENCY=EUR
```

The `private_key` config accepts either a file path or a raw PEM string.

The published `config/borica.php` nests merchant configuration under a `cgi` key:

```php
return [
    'cgi' => [
        'default' => env('BORICA_MERCHANT', 'default'),
        'merchants' => [
            'default' => [
                'terminal'               => env('BORICA_TERMINAL'),
                'merchant_id'            => env('BORICA_MERCHANT_ID'),
                'merchant_name'          => env('BORICA_MERCHANT_NAME'),
                'environment'            => env('BORICA_ENVIRONMENT', 'development'),
                'currency'               => env('BORICA_CURRENCY', 'EUR'),
                'private_key'            => env('BORICA_PRIVATE_KEY'),
                'private_key_passphrase' => env('BORICA_PRIVATE_KEY_PASSPHRASE'),
            ],
        ],
    ],

    'checkout' => [
        'default' => env('BORICA_CHECKOUT_MERCHANT', 'default'),
        'merchants' => [
            'default' => [
                'base_url'               => env('BORICA_CHECKOUT_BASE_URL'),
                'auth_id'                => env('BORICA_CHECKOUT_AUTH_ID'),
                'auth_secret'            => env('BORICA_CHECKOUT_AUTH_SECRET'),
                'shop_id'                => env('BORICA_CHECKOUT_SHOP_ID'),
                'private_key'            => env('BORICA_CHECKOUT_PRIVATE_KEY'),
                'private_key_passphrase' => env('BORICA_CHECKOUT_PRIVATE_KEY_PASSPHRASE'),
            ],
        ],
    ],

    'routes' => [
        'enabled'    => true,
        'prefix'     => 'borica',
        'middleware' => ['web'],
    ],

    'redirect' => [
        'success' => '/payment/success',
        'failure' => '/payment/failure',
    ],
];
```

### Facade

```php
use Ux2Dev\Borica\Laravel\Facades\Borica;

// Create a payment request using the default merchant
$request = Borica::payments()->purchase(
    amount: '49.99',
    order: '000001',
    description: 'Order #000001',
    mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
);

$gatewayUrl = Borica::getGatewayUrl();
$formFields = $request->toArray();
```

### Multiple Merchants

Define additional merchants in `config/borica.php`:

```php
'cgi' => [
    'default' => env('BORICA_MERCHANT', 'default'),
    'merchants' => [
        'default' => [ ... ],
        'second-shop' => [
            'terminal' => env('BORICA_SECOND_TERMINAL'),
            'merchant_id' => env('BORICA_SECOND_MERCHANT_ID'),
            // ...
        ],
    ],
],
```

Use a named merchant:

```php
Borica::merchant('second-shop')->payments()->purchase(...);
```

Or use the explicit `cgi()` accessor:

```php
Borica::cgi('second-shop')->payments()->purchase(...);
```

Or pass a runtime config array (e.g. from a database):

```php
Borica::merchant([
    'terminal' => $tenant->borica_terminal,
    'merchant_id' => $tenant->borica_merchant_id,
    'merchant_name' => $tenant->company_name,
    'private_key' => $tenant->borica_private_key_path,
    'environment' => $tenant->borica_environment,
    'currency' => $tenant->currency,
])->payments()->purchase(...);
```

### Dynamic Terminal Resolution

For multi-tenant applications where merchants are stored in a database, register a custom terminal resolver in a service provider:

```php
use Ux2Dev\Borica\Laravel\Facades\Borica;

public function boot(): void
{
    Borica::resolveTerminalUsing(function (string $terminal): ?array {
        $tenant = Tenant::where('borica_terminal', $terminal)->first();
        if (!$tenant) return null;

        return [
            'name' => $tenant->slug,
            'terminal' => $tenant->borica_terminal,
            'merchant_id' => $tenant->borica_merchant_id,
            'merchant_name' => $tenant->company_name,
            'private_key' => $tenant->borica_private_key_path,
            'environment' => $tenant->borica_environment,
            'currency' => $tenant->currency,
        ];
    });
}
```

This resolver is used automatically when BORICA sends callbacks -- the middleware looks up the merchant by the `TERMINAL` field in the POST data.

### Callback Handling

The package registers a `POST /borica/callback` route that:

1. Verifies the `P_SIGN` signature via the `VerifyBoricaSignature` middleware
2. Dispatches events based on the transaction result
3. Redirects to `config('borica.redirect.success')` or `config('borica.redirect.failure')`

The callback route is automatically excluded from CSRF verification.

#### Events

Listen for these events to process payment results:

| Event | When |
|---|---|
| `BoricaResponseReceived` | Every callback, regardless of result |
| `BoricaPaymentSucceeded` | Purchase (type 1) succeeded |
| `BoricaPaymentFailed` | Purchase (type 1) failed |
| `BoricaPreAuthSucceeded` | Pre-auth (type 12) succeeded |
| `BoricaPreAuthFailed` | Pre-auth (type 12) failed |

```php
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentSucceeded;

class HandlePayment
{
    public function handle(BoricaPaymentSucceeded $event): void
    {
        $response = $event->response;
        $merchantName = $event->merchantName;

        // Mark order as paid
        Order::where('borica_order', $response->getOrder())
            ->update(['status' => 'paid', 'rrn' => $response->getRrn()]);
    }
}
```

#### Customizing Routes

Publish the routes file to customize the callback endpoint:

```bash
php artisan vendor:publish --tag=borica-routes
```

Or disable the built-in route entirely and define your own:

```php
// config/borica.php
'routes' => ['enabled' => false],
```

### Artisan Commands

#### Generate Certificate

Interactive command to generate an RSA private key and CSR for BORICA merchant registration:

```bash
php artisan borica:generate-certificate
php artisan borica:generate-certificate --merchant=default  # pre-fills terminal from config
```

#### Status Check

Check the status of a transaction:

```bash
php artisan borica:status-check 000001 --type=purchase
php artisan borica:status-check 000001 --type=pre-auth --merchant=second-shop
```

Valid `--type` values: `purchase`, `pre-auth`, `pre-auth-complete`, `pre-auth-reversal`, `reversal`.

## Security

- Request signing uses **RSA-SHA256** via OpenSSL
- Response `P_SIGN` is verified against BORICA's public key before any data is returned
- Private key material is never exposed through `var_dump`, serialization, or public properties
- Sensitive response fields (CARD, APPROVAL, P_SIGN, RRN, INT_REF, CARDHOLDERINFO) are redacted in exception data and serialization
- Nonce (128-bit random) and timestamp are auto-generated per request to prevent replay
- BORICA public keys include integrity fingerprints to detect tampering

## Testing

The library uses [Pest](https://pestphp.com/) for testing. The test suite covers all transaction types, signing/verification, MAC construction, configuration validation, response parsing, and error codes.

### Running the tests

```bash
composer install
vendor/bin/pest
```

### Test structure

```
tests/
  CgiClientTest.php                      # Integration tests (full request/response round-trip)
  Config/MerchantConfigTest.php          # Config validation, defaults, serialization guard
  Certificate/CertificateGeneratorTest.php  # CSR/key generation, validation, file output
  Signing/SignerTest.php                 # RSA-SHA256 sign/verify, passphrase, invalid keys
  Signing/MacGeneralTest.php             # MAC field ordering for all transaction types
  Cgi/Request/PaymentRequestTest.php     # Payment request fields and signing fields
  Cgi/Request/PreAuthRequestTest.php     # Pre-authorization request
  Cgi/Request/PreAuthCompleteRequestTest.php
  Cgi/Request/PreAuthReversalRequestTest.php
  Cgi/Request/ReversalRequestTest.php
  Cgi/Request/StatusCheckRequestTest.php
  Cgi/Response/ResponseParserTest.php    # P_SIGN verification, tampered/missing signatures
  Cgi/Response/ResponseTest.php          # Response object, success/failure, error messages
  ErrorCode/GatewayErrorTest.php         # Gateway error code lookups
  ErrorCode/IssuerErrorTest.php          # Issuer error code lookups
  Laravel/
    TestCase.php                         # Orchestra Testbench base class
    BoricaServiceProviderTest.php        # Config merging, singleton, routes, commands
    BoricaManagerTest.php               # Multi-merchant resolution, caching, key resolution
    FacadeTest.php                       # Facade proxy verification
    BoricaCallbackControllerTest.php     # Event dispatching, redirects
    VerifyBoricaSignatureTest.php        # Signature verification, 403 on failure
    EventsTest.php                       # All 5 event classes
    ConfigResolutionTest.php             # Config structure validation
    GenerateCertificateCommandTest.php   # Certificate generation command
    StatusCheckCommandTest.php           # Status check command
  fixtures/
    test_private_key.pem                 # Unencrypted RSA 2048-bit key (test only)
    test_private_key_encrypted.pem       # Passphrase-protected key (passphrase: "testpass")
    test_public_key.pem                  # Matching public key
```

### Test fixtures

The `tests/fixtures/` directory contains RSA key pairs for testing only. These keys are not used in any environment and have no relation to BORICA's actual keys. The test suite uses them for sign/verify round-trips without requiring a real merchant account.

### Writing tests against the library

When testing your own integration code, you can create a test `CgiClient` instance using the development environment and your own test keys. For response parsing tests, sign a mock response with your test private key and pass the matching public key to `parse()`:

```php
$response = $cgi->responses()->parse(
    $mockResponseData,
    TransactionType::Purchase,
    $testPublicKey,  // override the BORICA public key for testing
);
```

## License

MIT -- see [LICENSE](LICENSE).
