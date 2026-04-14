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

        $keyPath = $directory . '/' . $this->privateKeyFilename;
        file_put_contents($keyPath, $this->privateKey);
        chmod($keyPath, 0600);

        file_put_contents($directory . '/' . $this->csrFilename, $this->csr);
    }

    public function __debugInfo(): array
    {
        return [
            'privateKey' => '[REDACTED]',
            'csr' => $this->csr,
            'privateKeyFilename' => $this->privateKeyFilename,
            'csrFilename' => $this->csrFilename,
        ];
    }

    public function __serialize(): array
    {
        throw new \LogicException(
            'CertificateResult must not be serialized as it contains private key material'
        );
    }

    public function __unserialize(array $data): void
    {
        throw new \LogicException(
            'CertificateResult must not be unserialized'
        );
    }
}
