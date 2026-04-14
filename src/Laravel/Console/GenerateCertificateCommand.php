<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Console;

use Illuminate\Console\Command;
use Ux2Dev\Borica\Certificate\CertificateGenerator;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\SigningException;

class GenerateCertificateCommand extends Command
{
    protected $signature = 'borica:generate-certificate
        {--merchant= : Pre-fill terminal ID from a configured merchant}';

    protected $description = 'Generate an RSA private key and CSR for BORICA merchant registration';

    public function handle(): int
    {
        $terminalId = $this->resolveTerminalId();
        if ($terminalId === null) {
            return self::FAILURE;
        }

        $env = $this->choice('Environment', ['development', 'production'], 0);
        $environment = $env === 'production' ? Environment::Production : Environment::Development;

        $domain = $this->ask('Domain (without protocol, e.g. "merchantdomain.bg")');
        $organization = $this->ask('Organization name');
        $city = $this->ask('City');
        $state = $this->ask('State/Region');
        $email = $this->ask('Email');
        $country = $this->ask('Country code (2-letter ISO)', 'BG');
        $outputDir = $this->ask('Output directory', storage_path('borica'));

        try {
            $result = CertificateGenerator::generate(
                terminalId: $terminalId,
                environment: $environment,
                commonName: $domain,
                organizationName: $organization,
                localityName: $city,
                stateOrProvinceName: $state,
                emailAddress: $email,
                countryCode: $country,
            );
        } catch (SigningException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0700, true);
        }

        $result->saveToDirectory($outputDir);

        $this->info("Private key saved to: {$outputDir}/{$result->privateKeyFilename}");
        $this->info("CSR saved to: {$outputDir}/{$result->csrFilename}");
        $this->newLine();
        $this->info('Upload the CSR to the BORICA Merchant Portal.');

        return self::SUCCESS;
    }

    private function resolveTerminalId(): ?string
    {
        $merchant = $this->option('merchant');

        if ($merchant !== null) {
            $terminal = config("borica.merchants.{$merchant}.terminal");

            if ($terminal === null) {
                $this->error("Merchant [{$merchant}] is not configured.");
                return null;
            }

            $this->info("Using terminal ID from merchant [{$merchant}]: {$terminal}");
            return $terminal;
        }

        $terminalId = $this->ask('Terminal ID (8 alphanumeric characters)');

        if (!preg_match('/^[A-Za-z0-9]{8}$/', $terminalId)) {
            $this->error('Terminal ID must be exactly 8 alphanumeric characters');
            return null;
        }

        return $terminalId;
    }
}
