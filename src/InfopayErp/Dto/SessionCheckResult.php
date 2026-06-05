<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\BoricaResult;

use Ux2Dev\Borica\InfopayErp\Enum\SessionState;

final readonly class SessionCheckResult implements BoricaResult
{
    public function __construct(
        public SessionState $state,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            state: SessionState::from((string) ($data['State'] ?? '')),
        );
    }
}
