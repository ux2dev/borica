<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;

/**
 * Result of POST /api/session. When Status is Success the sessionId and
 * sessionKey are populated and must be sent as SessionId / SessionKey
 * headers on every subsequent authenticated request.
 */
final readonly class Session
{
    public function __construct(
        public string $sessionId,
        public SessionCreateStatus $status,
        public ?string $sessionKey = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['SessionId'] ?? ''),
            status: SessionCreateStatus::from((string) ($data['Status'] ?? '')),
            sessionKey: isset($data['SessionKey']) ? (string) $data['SessionKey'] : null,
        );
    }

    /**
     * Authentication headers for every subsequent ERP API call.
     *
     * @return array<string, string>
     */
    public function authHeaders(): array
    {
        if ($this->sessionKey === null) {
            return [];
        }

        return [
            'SessionId' => $this->sessionId,
            'SessionKey' => $this->sessionKey,
        ];
    }
}
