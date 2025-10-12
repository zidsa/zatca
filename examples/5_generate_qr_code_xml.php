<?php

require_once("../vendor/autoload.php");

use Zid\Zatca\Entities\CSID;
use Zid\Zatca\QrCodeGeneratorService;

$invoiceData = json_decode(file_get_contents('output/simplified/invoice/invoice.json'), true);
$canonicalXml = base64_decode($invoiceData['invoice']);
$base64Hash = $invoiceData['invoiceHash'];
$ccsid = CSID::loadFromJson('output/ccsid.json');

$signatureValue = '....';
$publicKeyAndSignatureService = new \Zid\Zatca\GetPublicKeyAndSignatureService();
$base64QrCode = (new QrCodeGeneratorService($publicKeyAndSignatureService))->generate(
    csid: $ccsid,
    invoiceHash: $base64Hash,
    canonicalXml: $canonicalXml,
    signatureValue: $signatureValue
);

$binaryData = base64_decode($base64QrCode);

$hexDump = unpack('H*', $binaryData)[1];

echo 'Generated QR Code:' . PHP_EOL;
echo $base64QrCode . PHP_EOL;

echo "Hex Dump QR Code:\n";
echo chunk_split($hexDump, 2, ' ');
