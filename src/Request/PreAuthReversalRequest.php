<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Request;

use Ux2Dev\Borica\Enum\TransactionType;

final readonly class PreAuthReversalRequest implements RequestInterface
{
    public function __construct(
        private string $terminal,
        private string $amount,
        private string $currency,
        private string $order,
        private string $timestamp,
        private string $nonce,
        private string $pSign,
        private string $merchant,
        private string $merchantName,
        private string $description,
        private string $rrn,
        private string $intRef,
        private string $adCustBorOrderId = '',
        private string $country = '',
        private string $merchGmt = '',
        private string $addendum = '',
        private string $email = '',
        private string $merchantUrl = '',
        private string $language = 'BG',
    ) {}

    public function getTransactionType(): TransactionType
    {
        return TransactionType::PreAuthReversal;
    }

    public function getSigningFields(): array
    {
        return [
            'TERMINAL' => $this->terminal,
            'TRTYPE' => (string) TransactionType::PreAuthReversal->value,
            'AMOUNT' => $this->amount,
            'CURRENCY' => $this->currency,
            'ORDER' => $this->order,
            'TIMESTAMP' => $this->timestamp,
            'NONCE' => $this->nonce,
        ];
    }

    public function toArray(): array
    {
        $data = [
            'TERMINAL' => $this->terminal,
            'TRTYPE' => (string) TransactionType::PreAuthReversal->value,
            'AMOUNT' => $this->amount,
            'CURRENCY' => $this->currency,
            'ORDER' => $this->order,
            'DESC' => $this->description,
            'MERCHANT' => $this->merchant,
            'MERCH_NAME' => $this->merchantName,
            'RRN' => $this->rrn,
            'INT_REF' => $this->intRef,
            'TIMESTAMP' => $this->timestamp,
            'NONCE' => $this->nonce,
            'P_SIGN' => $this->pSign,
            'LANG' => $this->language,
        ];

        if ($this->adCustBorOrderId !== '') {
            $data['AD.CUST_BOR_ORDER_ID'] = $this->adCustBorOrderId;
        }
        if ($this->addendum !== '') {
            $data['ADDENDUM'] = $this->addendum;
        }
        if ($this->country !== '') {
            $data['COUNTRY'] = $this->country;
        }
        if ($this->merchGmt !== '') {
            $data['MERCH_GMT'] = $this->merchGmt;
        }
        if ($this->email !== '') {
            $data['EMAIL'] = $this->email;
        }
        if ($this->merchantUrl !== '') {
            $data['MERCH_URL'] = $this->merchantUrl;
        }

        return $data;
    }
}
