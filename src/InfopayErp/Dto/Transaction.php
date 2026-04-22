<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;
use Ux2Dev\Borica\InfopayErp\Enum\TransactionType;

final readonly class Transaction
{
    public function __construct(
        public AmountType $transactionAmount,
        public ?string $transactionId = null,
        public ?string $transactionExternalId = null,
        public ?DateTimeImmutable $bookingDate = null,
        public ?DateTimeImmutable $valueDate = null,
        public ?TransactionType $transactionType = null,
        public ?string $remittanceInformationUnstructured = null,
        public ?DateTimeImmutable $timeStamp = null,
        public ?string $debtorAccount = null,
        public ?string $debtorName = null,
        public ?string $creditorAccount = null,
        public ?string $creditorName = null,
        public ?string $entryReference = null,
        public ?string $purposeCode = null,
        public ?string $bankTransactionCode = null,
        public ?string $proprietaryBankTransactionCode = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionAmount: AmountType::fromArray((array) ($data['TransactionAmount'] ?? [])),
            transactionId: isset($data['TransactionId']) ? (string) $data['TransactionId'] : null,
            transactionExternalId: isset($data['TransactionExternalId']) ? (string) $data['TransactionExternalId'] : null,
            bookingDate: isset($data['BookingDate']) ? new DateTimeImmutable((string) $data['BookingDate']) : null,
            valueDate: isset($data['ValueDate']) ? new DateTimeImmutable((string) $data['ValueDate']) : null,
            transactionType: isset($data['TransactionType']) ? TransactionType::from((string) $data['TransactionType']) : null,
            remittanceInformationUnstructured: isset($data['RemittanceInformationUnstructured']) ? (string) $data['RemittanceInformationUnstructured'] : null,
            timeStamp: isset($data['TimeStamp']) ? new DateTimeImmutable((string) $data['TimeStamp']) : null,
            debtorAccount: isset($data['DebtorAccount']) ? (string) $data['DebtorAccount'] : null,
            debtorName: isset($data['DebtorName']) ? (string) $data['DebtorName'] : null,
            creditorAccount: isset($data['CreditorAccount']) ? (string) $data['CreditorAccount'] : null,
            creditorName: isset($data['CreditorName']) ? (string) $data['CreditorName'] : null,
            entryReference: isset($data['EntryReference']) ? (string) $data['EntryReference'] : null,
            purposeCode: isset($data['PurposeCode']) ? (string) $data['PurposeCode'] : null,
            bankTransactionCode: isset($data['BankTransactionCode']) ? (string) $data['BankTransactionCode'] : null,
            proprietaryBankTransactionCode: isset($data['ProprietaryBankTransactionCode']) ? (string) $data['ProprietaryBankTransactionCode'] : null,
        );
    }
}
