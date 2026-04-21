<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Certificate;

use DateTimeImmutable;

final readonly class CertificateInfo
{
    public function __construct(
        public DateTimeImmutable $notBefore,
        public DateTimeImmutable $notAfter,
        public string $subject,
        public string $issuer,
        public string $serialNumber,
    ) {}

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        return ($now ?? new DateTimeImmutable()) >= $this->notAfter;
    }

    public function isNotYetValid(?DateTimeImmutable $now = null): bool
    {
        return ($now ?? new DateTimeImmutable()) < $this->notBefore;
    }

    public function daysUntilExpiry(?DateTimeImmutable $now = null): int
    {
        $now = $now ?? new DateTimeImmutable();
        $diff = $now->diff($this->notAfter);
        $days = (int) $diff->days;
        return $diff->invert ? -$days : $days;
    }

    public function isExpiringSoon(int $warningDays = 30, ?DateTimeImmutable $now = null): bool
    {
        $days = $this->daysUntilExpiry($now);
        return $days >= 0 && $days <= $warningDays;
    }
}
