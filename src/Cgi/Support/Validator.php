<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Support;

use Ux2Dev\Borica\Exception\ConfigurationException;

final class Validator
{
    public static function amount(string $amount): void
    {
        if (!preg_match('/^\d{1,12}\.\d{2}$/', $amount)) {
            throw new ConfigurationException(
                'Amount must be a positive number with exactly 2 decimal places (e.g. "10.50")'
            );
        }
        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new ConfigurationException('Amount must be greater than zero');
        }
    }

    public static function order(string $order): void
    {
        if (!preg_match('/^\d{6}$/', $order)) {
            throw new ConfigurationException('Order must be exactly 6 digits (zero-padded, e.g. "000001")');
        }
    }

    public static function description(string $description): void
    {
        if ($description === '') {
            throw new ConfigurationException('Description must not be empty');
        }
        if (mb_strlen($description) > 50) {
            throw new ConfigurationException('Description must not exceed 50 characters');
        }
    }

    public static function email(string $email): void
    {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ConfigurationException('Invalid email format');
        }
    }

    public static function merchantUrl(string $url): void
    {
        if ($url === '') {
            return;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException('Invalid merchant URL');
        }
        if (!str_starts_with($url, 'https://')) {
            throw new ConfigurationException('Merchant URL must use HTTPS');
        }
    }

    /**
     * @param array<string, mixed> $mInfo
     */
    public static function mInfo(array $mInfo): void
    {
        if (!isset($mInfo['cardholderName']) || !is_string($mInfo['cardholderName']) || $mInfo['cardholderName'] === '') {
            throw new ConfigurationException('M_INFO must contain a non-empty "cardholderName"');
        }
        if (mb_strlen($mInfo['cardholderName']) > 45) {
            throw new ConfigurationException('M_INFO "cardholderName" must not exceed 45 characters');
        }
        $hasEmail = isset($mInfo['email']) && is_string($mInfo['email']) && $mInfo['email'] !== '';
        $hasPhone = isset($mInfo['mobilePhone']) && is_array($mInfo['mobilePhone']);
        if (!$hasEmail && !$hasPhone) {
            throw new ConfigurationException('M_INFO must contain "email" and/or "mobilePhone"');
        }
    }

    public static function resolveTimestamp(?string $timestamp): string
    {
        if ($timestamp === null) {
            return gmdate('YmdHis');
        }
        if (!preg_match('/^\d{14}$/', $timestamp)) {
            throw new ConfigurationException('Timestamp must be exactly 14 digits (YmdHis format)');
        }
        return $timestamp;
    }

    public static function resolveNonce(?string $nonce): string
    {
        if ($nonce === null) {
            return strtoupper(bin2hex(random_bytes(16)));
        }
        if (!preg_match('/^[A-F0-9]{32}$/', $nonce)) {
            throw new ConfigurationException('Nonce must be exactly 32 uppercase hex characters');
        }
        return $nonce;
    }

    /**
     * @param array<string, mixed> $mInfo
     */
    public static function encodeMInfo(array $mInfo): string
    {
        $encoded = base64_encode(json_encode($mInfo, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        if (strlen($encoded) > 35000) {
            throw new ConfigurationException('M_INFO data exceeds maximum allowed size of 35000 bytes');
        }
        return $encoded;
    }

    public static function resolveAdCustBorOrderId(string $adCustBorOrderId, string $order): string
    {
        $value = $adCustBorOrderId !== '' ? $adCustBorOrderId : $order;
        $value = str_replace(';', '', $value);
        return mb_substr($value, 0, 22);
    }
}
