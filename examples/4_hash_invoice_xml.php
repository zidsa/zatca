<?php
require_once("../vendor/autoload.php");;

$xmlDocumentPath = 'output/unsigned_simplified_invoice_standard.xml';
$xmlContent = file_get_contents($xmlDocumentPath);

$hashingService = new \Zid\Zatca\InvoiceHashingService();
$result = $hashingService->hash($xmlContent);

var_dump([
    "invoiceHash" => $result->invoiceHash,
    "uuid" => $result->uuid,
    "invoice" => $result->b64Invoice,
    'base64CanonicalXml' => $result->base64CanonicalXml,
]);

file_put_contents('output/simplified/invoice/invoice.json', json_encode($result, JSON_PRETTY_PRINT));
