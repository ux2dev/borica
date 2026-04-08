<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Certificate;

final readonly class CertificateResult
{
    public function __construct(
        public string $privateKey,
        public string $csr,
        public string $privateKeyFilename,
        public string $csrFilename,
    ) {}

    public function saveToDirectory(string $directory): void
    {
        $directory = rtrim($directory, '/');

        file_put_contents($directory . '/' . $this->privateKeyFilename, $this->privateKey);
        file_put_contents($directory . '/' . $this->csrFilename, $this->csr);
    }
}
