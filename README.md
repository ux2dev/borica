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

## Configuration

Create a `MerchantConfig` instance with your merchant credentials:

```php
use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;

$config = new MerchantConfig(
    terminal: 'V1800001',
    merchantId: '1600000001',
    merchantName: 'My Shop',
    privateKey: file_get_contents('/path/to/private_key.pem'),
    environment: Environment::Development,  // or Environment::Production
    currency: Currency::BGN,                // BGN, EUR, or USD
    country: 'BG',                          // default: 'BG'
    timezoneOffset: '+03',                  // default: '+03'
    privateKeyPassphrase: 'secret',         // optional, if key is encrypted
);

$borica = new Borica($config);
```

The config validates all inputs on construction. The private key and passphrase are never exposed through public properties or serialization.

### PSR-3 Logging

Pass any PSR-3 logger as the second argument:

```php
$borica = new Borica($config, $logger);
```

### Gateway URLs

The gateway URL is determined by the environment:

| Environment   | URL                                                  |
|---------------|------------------------------------------------------|
| Development   | `https://3dsgate-dev.borica.bg/cgi-bin/cgi_link`     |
| Production    | `https://3dsgate.borica.bg/cgi-bin/cgi_link`         |

```php
$gatewayUrl = $borica->getGatewayUrl();
```

## Usage

### Payment (Transaction Type 1)

Browser-based payment. Build the request, then POST the form data to the gateway URL.

```php
$request = $borica->createPaymentRequest(
    amount: '49.99',
    order: '000001',
    description: 'Order #000001',
);

// Build an auto-submitting HTML form
$gatewayUrl = $borica->getGatewayUrl();
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
$request = $borica->createPaymentRequest(
    amount: '49.99',
    order: '000001',
    description: 'Order #000001',
    adCustBorOrderId: 'MY-SHOP-1234',     // custom order ID shown to customer
    mInfo: ['cardholderName' => 'John'],   // additional merchant info (base64-encoded JSON)
    language: 'EN',                        // form language (default: 'BG')
    email: 'customer@example.com',         // customer email
    merchantUrl: 'https://shop.com/notify', // notification URL (must be HTTPS)
);
```

### Pre-Authorization (Transaction Type 12)

Reserves an amount on the customer's card without capturing it. Same interface as payment.

```php
$request = $borica->createPreAuthRequest(
    amount: '100.00',
    order: '000002',
    description: 'Pre-auth for booking #000002',
);

$formFields = $request->toArray();
// POST to $borica->getGatewayUrl()
```

### Complete Pre-Authorization (Transaction Type 21)

Captures a previously pre-authorized amount. Server-to-server -- POST directly to the gateway.

```php
$request = $borica->createPreAuthCompleteRequest(
    amount: '100.00',
    order: '000002',
    rrn: $preAuthResponse->getRrn(),       // RRN from the pre-auth response
    intRef: $preAuthResponse->getIntRef(), // INT_REF from the pre-auth response
    description: 'Capture booking #000002',
);

// POST $request->toArray() to $borica->getGatewayUrl() via HTTP client
```

### Reverse Pre-Authorization (Transaction Type 22)

Releases a pre-authorized hold.

```php
$request = $borica->createPreAuthReversalRequest(
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
$request = $borica->createReversalRequest(
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

$request = $borica->createStatusCheckRequest(
    order: '000001',
    transactionType: TransactionType::Purchase, // type of the original transaction
);

// POST $request->toArray() to $borica->getGatewayUrl() via HTTP client
```

### Parsing the Gateway Response

When BORICA redirects back to your site (for browser-based transactions) or returns an HTTP response (for server-to-server transactions), parse and verify it:

```php
// $data is the associative array from the gateway (e.g. $_POST for callbacks)
$response = $borica->parseResponse($data, TransactionType::Purchase);

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
    $response = $borica->parseResponse($data, TransactionType::Purchase);
} catch (InvalidResponseException $e) {
    // Signature verification failed -- do not trust this response
    log($e->getMessage());
    log($e->getResponseData()); // sensitive fields are redacted
} catch (BoricaException $e) {
    // Any other library error
}
```

## Security

- Request signing uses **RSA-SHA256** via OpenSSL
- Response `P_SIGN` is verified against BORICA's public key before any data is returned
- Private key material is never exposed through `var_dump`, serialization, or public properties
- Sensitive response fields (CARD, APPROVAL, P_SIGN) are redacted in exception data and serialization
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
  BoricaTest.php                       # Integration tests (full request/response round-trip)
  Config/MerchantConfigTest.php        # Config validation, defaults, serialization guard
  Signing/SignerTest.php               # RSA-SHA256 sign/verify, passphrase, invalid keys
  Signing/MacGeneralTest.php           # MAC field ordering for all transaction types
  Request/PaymentRequestTest.php       # Payment request fields and signing fields
  Request/PreAuthRequestTest.php       # Pre-authorization request
  Request/PreAuthCompleteRequestTest.php
  Request/PreAuthReversalRequestTest.php
  Request/ReversalRequestTest.php
  Request/StatusCheckRequestTest.php
  Response/ResponseParserTest.php      # P_SIGN verification, tampered/missing signatures
  Response/ResponseTest.php            # Response object, success/failure, error messages
  ErrorCode/GatewayErrorTest.php       # Gateway error code lookups
  ErrorCode/IssuerErrorTest.php        # Issuer error code lookups
  fixtures/
    test_private_key.pem               # Unencrypted RSA 2048-bit key (test only)
    test_private_key_encrypted.pem     # Passphrase-protected key (passphrase: "testpass")
    test_public_key.pem                # Matching public key
```

### Test fixtures

The `tests/fixtures/` directory contains RSA key pairs for testing only. These keys are not used in any environment and have no relation to BORICA's actual keys. The test suite uses them for sign/verify round-trips without requiring a real merchant account.

### Writing tests against the library

When testing your own integration code, you can create a test `Borica` instance using the development environment and your own test keys. For response parsing tests, sign a mock response with your test private key and pass the matching public key to `parseResponse()`:

```php
$response = $borica->parseResponse(
    $mockResponseData,
    TransactionType::Purchase,
    $testPublicKey,  // override the BORICA public key for testing
);
```

## License

MIT -- see [LICENSE](LICENSE).
