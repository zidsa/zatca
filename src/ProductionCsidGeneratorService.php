<?php

namespace Zid\Zatca;

use Zid\Zatca\API\ZatcaClient;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Enums\ZatcaEnvironment;

class ProductionCsidGeneratorService
{
    private ZatcaClient $zatcaClient;

    public function __construct(
        ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
    ) {
        $this->zatcaClient = new ZatcaClient($environment);
    }

    public function requestProductionCertificate(string $binarySecurityToken, string $secret, string $ccsidRequestId): CSID
    {
        $result = $this->zatcaClient->productionApi()->requestProductionCertificate($binarySecurityToken, $secret, $ccsidRequestId);

        return new CSID(
            certificate: $result['binarySecurityToken'],
            secret: $result['secret'],
            requestId: $result['requestID'],
        );
    }
}
