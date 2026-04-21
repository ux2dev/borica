<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Support;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use Ux2Dev\Borica\Cgi\Request\RequestInterface;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

/**
 * Shared signing behaviour for CGI resources.
 *
 * Consuming class must expose:
 *   protected MerchantConfig $config
 *   protected MacGeneral $macGeneral
 *   protected Signer $signer
 *   protected LoggerInterface $logger
 */
trait SignsRequests
{
    /**
     * @template T of RequestInterface
     * @param  T $request
     * @return T
     */
    protected function signRequest(RequestInterface $request): RequestInterface
    {
        $fields = $request->getSigningFields();
        $fields['MERCHANT'] = $this->config->merchantId;

        $signingData = $this->macGeneral->buildRequestSigningData(
            $request->getTransactionType(),
            $fields,
            $this->config->signingSchema,
        );
        $pSign = $this->signer->sign(
            $signingData,
            $this->config->getPrivateKey(),
            $this->config->getPrivateKeyPassphrase(),
        );
        $this->logger->debug('Signed BORICA request', ['trtype' => $request->getTransactionType()->value]);

        $class = new ReflectionClass($request);
        $params = [];
        foreach ($class->getConstructor()->getParameters() as $param) {
            $name = $param->getName();
            $prop = $class->getProperty($name);
            $value = $prop->getValue($request);
            $params[$name] = $name === 'pSign' ? $pSign : $value;
        }
        return $class->newInstanceArgs($params);
    }
}
