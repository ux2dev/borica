<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;

class BoricaManager
{
    /** @var array<string, Borica> */
    private array $merchants = [];

    private ?\Closure $terminalResolver = null;

    /** @var array<string, array> Configs resolved via custom terminal resolver, pending merchant() call. */
    private array $resolvedConfigs = [];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * Register a custom resolver for terminal-based merchant lookup.
     *
     * The callback receives a terminal ID and should return a merchant
     * config array (same shape as borica.merchants.* entries) or null.
     * Include a 'name' key to control the merchant identifier used in events.
     *
     * Example (multi-tenant):
     *   $manager->resolveTerminalUsing(function (string $terminal): ?array {
     *       $tenant = Tenant::where('borica_terminal', $terminal)->first();
     *       if (!$tenant) return null;
     *       return [
     *           'name' => $tenant->slug,
     *           'terminal' => $tenant->borica_terminal,
     *           'merchant_id' => $tenant->borica_merchant_id,
     *           'merchant_name' => $tenant->company_name,
     *           'private_key' => $tenant->borica_private_key_path,
     *           'environment' => $tenant->borica_environment,
     *           'currency' => $tenant->currency,
     *       ];
     *   });
     *
     * @param callable(string): ?array $resolver
     */
    public function resolveTerminalUsing(callable $resolver): void
    {
        $this->terminalResolver = $resolver(...);
    }

    /**
     * Resolve a Borica instance by merchant name or runtime array config.
     *
     * Named merchants are cached; array configs create a new instance each time.
     */
    public function merchant(string|array|null $name = null): Borica
    {
        if (is_array($name)) {
            return $this->buildMerchant($name);
        }

        $name = $name ?? $this->config->get('borica.default', 'default');

        if (isset($this->merchants[$name])) {
            return $this->merchants[$name];
        }

        // Check configs resolved by the custom terminal resolver
        if (isset($this->resolvedConfigs[$name])) {
            $this->merchants[$name] = $this->buildMerchant($this->resolvedConfigs[$name]);
            unset($this->resolvedConfigs[$name]);

            return $this->merchants[$name];
        }

        $merchantConfig = $this->config->get("borica.merchants.{$name}");

        if ($merchantConfig === null) {
            throw new InvalidArgumentException("Borica merchant [{$name}] is not configured");
        }

        $this->merchants[$name] = $this->buildMerchant($merchantConfig);

        return $this->merchants[$name];
    }

    /**
     * Find a resolved Borica instance by terminal ID across all configured merchants.
     */
    public function merchantByTerminal(string $terminal): ?Borica
    {
        $name = $this->findMerchantNameByTerminal($terminal);

        if ($name === null) {
            return null;
        }

        return $this->merchant($name);
    }

    /**
     * Find the merchant config name that matches the given terminal ID.
     *
     * Checks static config first, then falls back to the custom terminal
     * resolver registered via resolveTerminalUsing().
     */
    public function findMerchantNameByTerminal(string $terminal): ?string
    {
        $merchants = $this->config->get('borica.merchants', []);

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

    /**
     * Get the gateway URL from the default merchant.
     */
    public function getGatewayUrl(): string
    {
        return $this->merchant()->getGatewayUrl();
    }

    /**
     * Proxy all method calls to the default merchant.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->merchant()->{$method}(...$parameters);
    }

    private function buildMerchant(array $config): Borica
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

        return new Borica(
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
