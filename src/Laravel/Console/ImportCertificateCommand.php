<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Console;

use Illuminate\Console\Command;
use Ux2Dev\Borica\Certificate\CertificateImporter;
use Ux2Dev\Borica\Exception\ConfigurationException;

class ImportCertificateCommand extends Command
{
    protected $signature = 'borica:certificate:import
        {pfx : Path to the PFX file from BORICA}
        {--output-dir= : Where to write extracted files (default: storage/borica)}
        {--key-name=private.key : Filename for the extracted private key}
        {--cert-name=certificate.pem : Filename for the extracted certificate}
        {--passphrase= : PFX passphrase (omit to be prompted)}';

    protected $description = 'Extract private key + certificate from a BORICA PFX bundle into PEM files';

    public function handle(): int
    {
        $pfxPath = $this->argument('pfx');
        if (!is_file($pfxPath)) {
            $this->error("PFX file not found: {$pfxPath}");
            return self::FAILURE;
        }

        $passphrase = $this->option('passphrase');
        if ($passphrase === null) {
            $passphrase = $this->secret('PFX passphrase (leave blank if none)') ?? '';
        }

        $outputDir = $this->option('output-dir') ?? storage_path('borica');

        try {
            $paths = CertificateImporter::importToFiles(
                pfxPath: $pfxPath,
                passphrase: $passphrase,
                outputDir: $outputDir,
                keyFilename: $this->option('key-name'),
                certFilename: $this->option('cert-name'),
            );
        } catch (ConfigurationException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Private key  -> {$paths['keyPath']}");
        $this->info("Certificate  -> {$paths['certPath']}");
        return self::SUCCESS;
    }
}
