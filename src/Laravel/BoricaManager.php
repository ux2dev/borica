<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Ux2Dev\Borica\Cgi\CgiClient;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\InfopayCheckout\CheckoutClient;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\ErpClient;

class BoricaManager
{
    /** @var array<string, CgiClient> */
    private array $merchants = [];

    /** @var array<string, CheckoutClient> */
    private array $checkoutClients = [];

    /** @var array<string, ErpClient> */
    private array $erpClients = [];

    private ?\Closure $terminalResolver = null;

    /** @var array<string, array> */
    private array $resolvedConfigs = [];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param callable(string): ?array $resolver
     */
    public function resolveTerminalUsing(callable $resolver): void
    {
        $this->terminalResolver = $resolver(...);
    }

    /**
     * Resolve a CgiClient by merchant name or runtime config array.
     */
    public function cgi(string|array|null $name = null): CgiClient
    {
        if (is_array($name)) {
            return $this->buildCgi($name);
        }

        $name = $name ?? $this->config->get('borica.cgi.default', 'default');

        if (isset($this->merchants[$name])) {
            return $this->merchants[$name];
        }

        if (isset($this->resolvedConfigs[$name])) {
            $this->merchants[$name] = $this->buildCgi($this->resolvedConfigs[$name]);
            unset($this->resolvedConfigs[$name]);
            return $this->merchants[$name];
        }

        $merchantConfig = $this->config->get("borica.cgi.merchants.{$name}");

        if ($merchantConfig === null) {
            throw new InvalidArgumentException("Borica CGI merchant [{$name}] is not configured");
        }

        $this->merchants[$name] = $this->buildCgi($merchantConfig);

        return $this->merchants[$name];
    }

    /**
     * Back-compat alias. Prefer cgi().
     */
    public function merchant(string|array|null $name = null): CgiClient
    {
        return $this->cgi($name);
    }

    public function merchantByTerminal(string $terminal): ?CgiClient
    {
        $name = $this->findMerchantNameByTerminal($terminal);
        return $name === null ? null : $this->cgi($name);
    }

    public function findMerchantNameByTerminal(string $terminal): ?string
    {
        $merchants = $this->config->get('borica.cgi.merchants', []);

        foreach ($merchants as $name => $merchantConfig) {
            if (($merchantConfig['terminal'] ?? null) === $terminal) {
                return $name;
            }
        }

        if ($this->terminalResolver) {
            $config = ($this->terminalResolver)($terminal);
            if ($config !== null) {
                $name = $config['name'] ?? $terminal;
                $this->resolvedConfigs[$name] = $config;
                return $name;
            }
        }

        return null;
    }

    public function getGatewayUrl(): string
    {
        return $this->cgi()->getGatewayUrl();
    }

    /**
     * Proxy calls to the default CgiClient for ergonomic chaining
     * like BoricaManager::payments()->purchase(...).
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->cgi()->{$method}(...$parameters);
    }

    /**
     * Resolve a CheckoutClient by merchant name or runtime config array.
     */
    public function checkout(string|array|null $name = null): CheckoutClient
    {
        if (is_array($name)) {
            return $this->buildCheckout($name);
        }

        $name = $name ?? $this->config->get('borica.checkout.default', 'default');

        if (isset($this->checkoutClients[$name])) {
            return $this->checkoutClients[$name];
        }

        $merchantConfig = $this->config->get("borica.checkout.merchants.{$name}");

        if ($merchantConfig === null) {
            throw new InvalidArgumentException("Borica Checkout merchant [{$name}] is not configured");
        }

        $this->checkoutClients[$name] = $this->buildCheckout($merchantConfig);

        return $this->checkoutClients[$name];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildCheckout(array $config): CheckoutClient
    {
        $privateKey = $this->resolveKey($config['private_key'] ?? '') ?? '';

        $certificate = '';
        if (!empty($config['certificate'])) {
            $certificate = $this->resolveKey($config['certificate']) ?? '';
        }

        $checkoutConfig = new CheckoutConfig(
            baseUrl: $config['base_url'] ?? '',
            authId: $config['auth_id'] ?? '',
            authSecret: $config['auth_secret'] ?? '',
            shopId: $config['shop_id'] ?? '',
            privateKey: $privateKey,
            certificate: $certificate,
            privateKeyPassphrase: $config['private_key_passphrase'] ?? null,
        );

        return new CheckoutClient(
            config: $checkoutConfig,
            httpClient: app(\Psr\Http\Client\ClientInterface::class),
            requestFactory: app(\Psr\Http\Message\RequestFactoryInterface::class),
            streamFactory: app(\Psr\Http\Message\StreamFactoryInterface::class),
        );
    }

    /**
     * Resolve an ErpClient by integration name or runtime config array.
     */
    public function erp(string|array|null $name = null): ErpClient
    {
        if (is_array($name)) {
            return $this->buildErp($name);
        }

        $name = $name ?? $this->config->get('borica.erp.default', 'default');

        if (isset($this->erpClients[$name])) {
            return $this->erpClients[$name];
        }

        $integrationConfig = $this->config->get("borica.erp.integrations.{$name}");

        if ($integrationConfig === null) {
            throw new InvalidArgumentException("Borica ERP integration [{$name}] is not configured");
        }

        $this->erpClients[$name] = $this->buildErp($integrationConfig);

        return $this->erpClients[$name];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildErp(array $config): ErpClient
    {
        $erpConfig = new ErpConfig(
            baseUrl: $config['base_url'] ?? '',
            uniqueId: $config['unique_id'] ?? '',
            accessToken: $config['access_token'] ?? '',
        );

        return new ErpClient(
            config: $erpConfig,
            httpClient: app(\Psr\Http\Client\ClientInterface::class),
            requestFactory: app(\Psr\Http\Message\RequestFactoryInterface::class),
            streamFactory: app(\Psr\Http\Message\StreamFactoryInterface::class),
        );
    }

    private function buildCgi(array $config): CgiClient
    {
        $privateKey = $this->resolveKey($config['private_key'] ?? '') ?? '';
        $boricaPublicKey = $this->resolveKey($config['borica_public_key'] ?? null);

        $environment = $this->resolveEnvironment($config['environment'] ?? 'development');
        $currency = Currency::from(strtoupper($config['currency'] ?? 'EUR'));

        $merchantConfig = new MerchantConfig(
            terminal: $config['terminal'],
            merchantId: $config['merchant_id'],
            merchantName: $config['merchant_name'],
            privateKey: $privateKey,
            environment: $environment,
            currency: $currency,
            country: $config['country'] ?? 'BG',
            timezoneOffset: $config['timezone_offset'] ?? '+03',
            privateKeyPassphrase: $config['private_key_passphrase'] ?? null,
        );

        return new CgiClient(
            config: $merchantConfig,
            boricaPublicKey: $boricaPublicKey,
        );
    }

    private function resolveKey(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        if (str_starts_with($key, '-----BEGIN')) {
            return $key;
        }

        $realPath = realpath($key);

        if ($realPath === false || !is_file($realPath)) {
            throw new InvalidArgumentException("Key file does not exist: {$key}");
        }

        $contents = file_get_contents($realPath);

        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read key file: {$key}");
        }

        if (!str_contains($contents, '-----BEGIN')) {
            throw new InvalidArgumentException("Key file does not contain a valid PEM key: {$key}");
        }

        return $contents;
    }

    private function resolveEnvironment(string $environment): Environment
    {
        $normalized = strtolower($environment);

        if ($normalized === 'production' || $normalized === 'prod') {
            return Environment::Production;
        }

        return Environment::Development;
    }
}
