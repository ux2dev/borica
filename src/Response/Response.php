<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Response;

use Ux2Dev\Borica\ErrorCode\GatewayError;
use Ux2Dev\Borica\ErrorCode\IssuerError;

final readonly class Response implements \JsonSerializable
{
    public function __construct(private array $data) {}

    public function isSuccessful(): bool
    {
        return $this->getAction() === '0' && $this->getRc() === '00';
    }

    public function isSoftDecline(): bool
    {
        return $this->getAction() === '21' && $this->getRc() === '1A';
    }

    public function getAction(): string { return $this->data['ACTION'] ?? ''; }
    public function getRc(): string { return $this->data['RC'] ?? ''; }
    public function getApproval(): ?string { return $this->getOptional('APPROVAL'); }
    public function getTerminal(): string { return $this->data['TERMINAL'] ?? ''; }
    public function getTrtype(): string { return $this->data['TRTYPE'] ?? ''; }
    public function getAmount(): ?string { return $this->getOptional('AMOUNT'); }
    public function getCurrency(): ?string { return $this->getOptional('CURRENCY'); }
    public function getOrder(): string { return $this->data['ORDER'] ?? ''; }
    public function getRrn(): ?string { return $this->getOptional('RRN'); }
    public function getIntRef(): ?string { return $this->getOptional('INT_REF'); }
    public function getCard(): ?string { return $this->getOptional('CARD'); }
    public function getCardBrand(): ?string { return $this->getOptional('CARD_BRAND'); }
    public function getEci(): ?string { return $this->getOptional('ECI'); }
    public function getParesStatus(): ?string { return $this->getOptional('PARES_STATUS'); }
    public function getTimestamp(): string { return $this->data['TIMESTAMP'] ?? ''; }
    public function getNonce(): string { return $this->data['NONCE'] ?? ''; }
    public function getStatusMessage(): ?string { return $this->getOptional('STATUSMSG'); }
    public function getAuthStepResult(): ?string { return $this->getOptional('AUTH_STEP_RES'); }
    public function getCardholderInfo(): ?string { return $this->getOptional('CARDHOLDERINFO'); }
    public function getTranDate(): ?string { return $this->getOptional('TRAN_DATE'); }
    public function getTranTrtype(): ?string { return $this->getOptional('TRAN_TRTYPE'); }
    public function getLang(): ?string { return $this->getOptional('LANG'); }

    public function getErrorMessage(): string
    {
        $rc = $this->getRc();
        if ($rc === '' || $rc === '00') { return ''; }
        if (str_starts_with($rc, '-')) { return GatewayError::getMessage($rc); }
        return IssuerError::getMessage($rc);
    }

    public function toSafeArray(): array
    {
        $safe = $this->data;
        foreach (['CARD', 'P_SIGN'] as $key) {
            if (isset($safe[$key])) {
                $safe[$key] = '[REDACTED]';
            }
        }
        return $safe;
    }

    public function __debugInfo(): array
    {
        return $this->toSafeArray();
    }

    public function jsonSerialize(): array
    {
        return $this->toSafeArray();
    }

    public function __serialize(): array
    {
        return $this->toSafeArray();
    }

    private function getOptional(string $key): ?string
    {
        $value = $this->data[$key] ?? null;
        return ($value !== null && $value !== '') ? $value : null;
    }
}
