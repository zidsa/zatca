<?php
require_once("../vendor/autoload.php");;

use Zid\Zatca\Enums\ZatcaEnvironment;

$complianceService = new \Zid\Zatca\ComplianceService(ZatcaEnvironment::SANDBOX);

$csr = file_get_contents('output/certificate.csr');
$ccsid = $complianceService->requestComplianceCertificate(
    csr: $csr,
    otp: '123456'
);

echo 'Compliance CSID:' . PHP_EOL;
echo $ccsid->certificate . PHP_EOL;
echo 'Secret:' . PHP_EOL;
echo $ccsid->secret . PHP_EOL;
echo 'Request ID:' . PHP_EOL;
echo $ccsid->requestId . PHP_EOL;


$ccsid->saveAsJson('output/ccsid.json');;
