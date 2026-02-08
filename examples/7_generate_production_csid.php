<?php
require_once("../vendor/autoload.php");;

use Zid\Zatca\Enums\ZatcaEnvironment;

$productionCsidGenerator = new \Zid\Zatca\ProductionCsidGeneratorService(ZatcaEnvironment::SANDBOX);

$ccsid = \Zid\Zatca\Entities\CSID::loadFromJson('output/ccsid.json');

$pcsid = $productionCsidGenerator->requestProductionCertificate(
    binarySecurityToken: $ccsid->certificate,
    secret: $ccsid->secret,
    ccsidRequestId: $ccsid->requestId,
);

echo 'Production CSID:' . PHP_EOL;
echo $pcsid->certificate . PHP_EOL;
echo 'Secret:' . PHP_EOL;
echo $pcsid->secret . PHP_EOL;
echo 'Request ID:' . PHP_EOL;
echo $pcsid->requestId . PHP_EOL;

$pcsid->saveAsJson('output/pcsid.json');;
