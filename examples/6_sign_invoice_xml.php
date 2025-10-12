<?php
require_once("../vendor/autoload.php");

$qrCode = file_get_contents('output/qr_code.txt');
$invoiceData = json_decode(file_get_contents('output/invoice.json'), true);
$canonicalXml = base64_decode($invoiceData['base64CanonicalXml']);
$uuid = $invoiceData['uuid'];
$base64Hash = $invoiceData['invoiceHash'];

$ccsid = \Zid\Zatca\Entities\CSID::loadFromJson('output/ccsid.json');
$privateKeyContent = file_get_contents('output/private.pem');
$privateKeyContent = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKeyContent);

$digitalSignatureService = new \Zid\Zatca\GetDigitalSignatureService();
$result = (new \Zid\Zatca\InvoiceSigningService($digitalSignatureService))->sign(
    csid: $ccsid,
    privateKeyContent: $privateKeyContent,
    qrCode: $qrCode,
    canonicalXml: $canonicalXml,
    invoiceUuid: $uuid,
    invoiceHash: $base64Hash
);

echo 'Invoice UUID status:' . PHP_EOL;
echo $result->uuid . PHP_EOL;
echo 'Invoice Hash:' . PHP_EOL;
echo $result->invoiceHash . PHP_EOL;
echo 'Signed invoice (Base64 encoded):' . PHP_EOL;
echo $result->b64SignedInvoice . PHP_EOL;

