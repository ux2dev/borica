<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Config\BoricaConfig;
use Ux2Dev\Borica\Config\CgiConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;

/**
 * Laravel integration. Resolves tenant configuration from `config/borica.php`
 * and exposes a lazy, cached {@see Borica} instance per tenant. Supports
 * immutable tenant switching via `->tenant('foo')`.
 */
final class BoricaManager
{
    /** @var array<string, Borica> */
    private array $instances = [];

    private string $currentTenant;

    private ?\Closure $terminalResolver = null;

    /** @var array<string, array<string, mixed>> */
    private array $resolvedTenants = [];

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly ?ClientInterface $httpClient = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->currentTenant = (string) ($config['default'] ?? 'default');
    }

    public function tenant(string $name): self
    {
        $clone = clone $this;
        $clone->currentTenant = $name;
        return $clone;
    }

    public function currentTenant(): string
    {
        return $this->currentTenant;
    }

    public function client(): Borica
    {
        return $this->instances[$this->currentTenant] ??= $this->build($this->currentTenant);
    }

    /** Forward service accessors: Borica::checkout()->..., Borica::cgi()->... */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->client()->{$method}(...$arguments);
    }

    /** @param callable(string): ?array $resolver */
    public function resolveTerminalUsing(callable $resolver): void
    {
        $this->terminalResolver = $resolver(...);
    }

    /** Map an inbound CGI callback terminal to a tenant name (for callback routing). */
    public function tenantByTerminal(string $terminal): ?string
    {
        $tenants = (array) ($this->config['tenants'] ?? []);
        foreach ($tenants as $name => $cfg) {
            if (is_array($cfg) && (($cfg['cgi']['terminal'] ?? null) === $terminal)) {
                return (string) $name;
            }
        }

        if ($this->terminalResolver !== null) {
            $cfg = ($this->terminalResolver)($terminal);
            if ($cfg !== null) {
                $name = (string) ($cfg['name'] ?? $terminal);
                $this->resolvedTenants[$name] = $cfg;
                return $name;
            }
        }

        return null;
    }

    private function build(string $tenant): Borica
    {
        $tenants = (array) ($this->config['tenants'] ?? []);
        $c = $this->resolvedTenants[$tenant] ?? ($tenants[$tenant] ?? null);

        if (! is_array($c)) {
            throw new ConfigurationException("Borica tenant \"{$tenant}\" is not configured");
        }

        $factory = new HttpFactory();

        return new Borica(
            config: $this->buildConfig($c),
            httpClient: $this->httpClient ?? new Client(),
            requestFactory: $this->requestFactory ?? $factory,
            streamFactory: $this->streamFactory ?? $factory,
            boricaPublicKey: isset($c['cgi']['borica_public_key'])
                ? $this->resolveKey($c['cgi']['borica_public_key'])
                : null,
        );
    }

    /** @param array<string, mixed> $c */
    private function buildConfig(array $c): BoricaConfig
    {
        $environment = $this->resolveEnvironment($c['environment'] ?? 'production');

        return new BoricaConfig(
            cgi: isset($c['cgi']) ? $this->buildCgiConfig($c['cgi'], $environment) : null,
            checkout: isset($c['checkout']) ? $this->buildCheckoutConfig($c['checkout']) : null,
            erp: isset($c['erp']) ? $this->buildErpConfig($c['erp']) : null,
            environment: $environment,
        );
    }

    /** @param array<string, mixed> $cfg */
    private function buildCgiConfig(array $cfg, Environment $tenantEnvironment): CgiConfig
    {
        return new CgiConfig(
            terminal: $cfg['terminal'],
            merchantId: $cfg['merchant_id'],
            merchantName: $cfg['merchant_name'],
            privateKey: $this->resolveKey($cfg['private_key'] ?? '') ?? '',
            environment: isset($cfg['environment']) ? $this->resolveEnvironment($cfg['environment']) : $tenantEnvironment,
            currency: Currency::from(strtoupper($cfg['currency'] ?? 'EUR')),
            country: $cfg['country'] ?? 'BG',
            timezoneOffset: $cfg['timezone_offset'] ?? '+03',
            privateKeyPassphrase: $cfg['private_key_passphrase'] ?? null,
        );
    }

    /** @param array<string, mixed> $cfg */
    private function buildCheckoutConfig(array $cfg): CheckoutConfig
    {
        return new CheckoutConfig(
            baseUrl: $cfg['base_url'] ?? '',
            authId: $cfg['auth_id'] ?? '',
            authSecret: $cfg['auth_secret'] ?? '',
            shopId: $cfg['shop_id'] ?? '',
            privateKey: $this->resolveKey($cfg['private_key'] ?? '') ?? '',
            certificate: ! empty($cfg['certificate']) ? ($this->resolveKey($cfg['certificate']) ?? '') : '',
            privateKeyPassphrase: $cfg['private_key_passphrase'] ?? null,
        );
    }

    /** @param array<string, mixed> $cfg */
    private function buildErpConfig(array $cfg): ErpConfig
    {
        return new ErpConfig(
            baseUrl: $cfg['base_url'] ?? '',
            uniqueId: $cfg['unique_id'] ?? '',
            accessToken: $cfg['access_token'] ?? '',
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
        if ($realPath === false || ! is_file($realPath)) {
            throw new ConfigurationException("Key file does not exist: {$key}");
        }
        $contents = file_get_contents($realPath);
        if ($contents === false || ! str_contains($contents, '-----BEGIN')) {
            throw new ConfigurationException("Key file is not a valid PEM key: {$key}");
        }
        return $contents;
    }

    private function resolveEnvironment(string $environment): Environment
    {
        $n = strtolower($environment);
        return ($n === 'production' || $n === 'prod') ? Environment::Production : Environment::Development;
    }
}
