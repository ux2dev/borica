<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Enum;

enum TransactionType: int
{
    case Purchase = 1;
    case PreAuth = 12;
    case PreAuthComplete = 21;
    case PreAuthReversal = 22;
    case Reversal = 24;
    case StatusCheck = 90;

    public function isBrowserBased(): bool
    {
        return match ($this) {
            self::Purchase, self::PreAuth => true,
            default => false,
        };
    }
}
