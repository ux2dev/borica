<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Config;

use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\SigningException;

final class BoricaPublicKeys
{
    // MPI_OW_APGW_D_2026 - APGW TEST public key for response verification
    private const TEST = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAya0nWBwWR19j/B8STchu
oADV295eP0nd0I3KWIeiiiPV4+xfzqOVguKOt086BrIRLAfTU46dURtwX3PaqiJw
fXa8lpr1kQWCqQH6q/nl6t9A5OOBWF34pFvxgRL64QaQgUTwP+l4sx4p6JFKV41y
itFrgnWaz9X/Y6SXGDTFKcRfDy1FrRTY6g+UTAJtPTUOA8yi53kSK2lO8P3+Bzr1
paBVLjvsSt+uj4Jbz1ssY2IeHqaZm3vW4he6A20Z/ZGE/n1+YQoEqP4NIXVAjrlJ
W+/Z5hvokGWEdf6Fmyz+gA3G+pgVIbiTovW2SgPBy0H6runURtYS6oM3FhPRGJ2Q
uQIDAQAB
-----END PUBLIC KEY-----';

    // MPI_OW_APGW_P_2026 - APGW PRODUCTION public key for response verification
    private const PRODUCTION = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8oqRwrBQKZdO+VPoDHFf
5giPRQkObyvXM8wDDm+kIPhC4gIR8Ch9sFZlQxa8ZE3cCDMsAviub6+RvTtkqy1p
C5abVJQhAIpmIX3NDf82+aD+kGuxIe6JpcFAfKhV0zEr5LzqDYNzhn2huDpv7W+Z
5zUjtwxP5Ob9/Lmw0ckF6XE3drzt0pK26p3ZKRicUh/cGBWQC7bGHpnSnNmvF5Fq
b6PLu6Gzq5RjtSnJG7q8T7DWL5iFVpSFMN0tLbfuCM0ZSc5xodrk84esRm36KMV+
lx3t6HQ1kvs7aQKbGq0TtBAbfQRlYBlgV2DamyOQfH6vMiD179bol4Ss0XvaYWzq
fwIDAQAB
-----END PUBLIC KEY-----';

    private const FINGERPRINTS = [
        'test' => 'd72f460ca950970cf6fff8b3863b0b47b0e9e9cd99ed7d5d271b355a1e26f7ee',
        'production' => 'f58260f403b237891fa97fddc68520b9f608fd385827aa3ab32e07a3ae1619a9',
    ];

    public static function getPublicKey(Environment $environment): string
    {
        $key = match ($environment) {
            Environment::Development => self::TEST,
            Environment::Production => self::PRODUCTION,
        };

        $expectedFingerprint = match ($environment) {
            Environment::Development => self::FINGERPRINTS['test'],
            Environment::Production => self::FINGERPRINTS['production'],
        };

        if (hash('sha256', $key) !== $expectedFingerprint) {
            throw new SigningException('Public key integrity check failed');
        }

        return $key;
    }
}
