<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Config;

use Ux2Dev\Borica\Exception\ConfigurationException;

/**
 * Credentials + base URL for the Infopay ERP Integration API.
 *
 * The spec (https://integration.infopay.bg/swagger/v1/integration_openapi.yaml)
 * does not declare a `servers:` block, so the base URL must be provided
 * explicitly by the integrator. `uniqueId` and `accessToken` are issued
 * to the merchant as part of the ERP registration in InfoPay and are
 * consumed by the POST /api/session endpoint to mint a session.
 */
final class ErpConfig
{
    public readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        public readonly string $uniqueId,
        public readonly string $accessToken,
    ) {
        if ($baseUrl === '') {
            throw new ConfigurationException('ERP baseUrl must not be empty');
        }
        if (!str_starts_with($baseUrl, 'https://')) {
            throw new ConfigurationException('ERP baseUrl must use https://');
        }
        $this->baseUrl = rtrim($baseUrl, '/');

        if ($uniqueId === '') {
            throw new ConfigurationException('ERP uniqueId must not be empty');
        }
        if ($accessToken === '') {
            throw new ConfigurationException('ERP accessToken must not be empty');
        }
    }
}
