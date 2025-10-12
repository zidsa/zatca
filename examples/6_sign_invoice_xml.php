<?php
require_once("../vendor/autoload.php");

$qrCode = file_get_contents('output/qr_code.txt');
$invoiceData = json_decode(file_get_contents('output/invoice.json'), true);
$canonicalXml = base64_decode($invoiceData['base64CanonicalXml']);
$base64Hash = $invoiceData['invoiceHash'];

$ccsid = \Zid\Zatca\Entities\CSID::loadFromJson('output/ccsid.json');
$privateKeyContent = file_get_contents('output/private.pem');
$privateKeyContent = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKeyContent);

$publicKeyAndSignatureService = new \Zid\Zatca\GetPublicKeyAndSignatureService();
$qrCodeGeneratorService = new \Zid\Zatca\QrCodeGeneratorService($publicKeyAndSignatureService);
$digitalSignatureService = new \Zid\Zatca\GetDigitalSignatureService();
$result = (new \Zid\Zatca\InvoiceSigningService($digitalSignatureService, $qrCodeGeneratorService))->sign(
    csid: $ccsid,
    privateKeyContent: $privateKeyContent,
    canonicalXml: $canonicalXml,
    invoiceHash: $base64Hash
);

$binaryData = base64_decode($result->b64QrCode);
$hexDump = unpack('H*', $binaryData)[1];

echo 'Invoice Signature:' . PHP_EOL;
echo $result->signature . PHP_EOL;
echo 'Signed invoice (Base64 encoded):' . PHP_EOL;
echo $result->b64SignedInvoice . PHP_EOL;
echo 'Generated QR Code:' . PHP_EOL;
echo $result->b64QrCode . PHP_EOL;

echo "Hex Dump QR Code:\n";
echo chunk_split($hexDump, 2, ' ');
file_put_contents('output/qr_code.txt', $result->b64QrCode);
file_put_contents('output/qr_code.png', $binaryData);

