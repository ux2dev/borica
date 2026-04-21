<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\SessionCreateStatus;

final readonly class Session
{
    public function __construct(
        public string $sessionId,
        public string $sessionKey,
        public SessionCreateStatus $status,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            sessionKey: (string) ($data['sessionKey'] ?? ''),
            status: SessionCreateStatus::from($data['status']),
        );
    }

    public function basicAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->sessionId . ':' . $this->sessionKey);
    }
}
