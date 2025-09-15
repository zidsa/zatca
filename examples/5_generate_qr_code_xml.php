<?php

require_once("../vendor/autoload.php");

use Zid\Zatca\Entities\CSID;
use Zid\Zatca\QrCodeGeneratorService;

$invoiceData = json_decode(file_get_contents('output/simplified/invoice/invoice.json'), true);
$canonicalXml = base64_decode($invoiceData['invoice']);
$base64Hash = $invoiceData['invoiceHash'];
$ccsid = CSID::loadFromJson('output/ccsid.json');

$privateKeyContent = file_get_contents('output/private.pem');
$privateKeyContent = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKeyContent);

$digitalSignatureService = new \Zid\Zatca\GetDigitalSignatureService();
$publicKeyAndSignatureService = new \Zid\Zatca\GetPublicKeyAndSignatureService();
$base64QrCode = (new QrCodeGeneratorService($digitalSignatureService, $publicKeyAndSignatureService))->generate(
    csid: $ccsid,
    invoiceHash: $base64Hash,
    canonicalXml: $canonicalXml,
    privateKeyContent: $privateKeyContent
);

$binaryData = base64_decode($base64QrCode);

$hexDump = unpack('H*', $binaryData)[1];

file_put_contents('output/qr_code.txt', $base64QrCode);
file_put_contents('output/qr_code.png', $binaryData);

echo 'Generated QR Code:' . PHP_EOL;
echo $base64QrCode . PHP_EOL;

echo "Hex Dump QR Code:\n";
echo chunk_split($hexDump, 2, ' ');
