<?php
require_once("../vendor/autoload.php");

use Zid\Zatca\Zatca;
use Zid\Zatca\Enums\ZatcaEnvironment;

// Initialize the Zatca facade
$zatca = new Zatca(ZatcaEnvironment::SANDBOX);

// Example 1: Generate CSR using the facade
$csrBuilder = $zatca->csrBuilder()
    ->setCommonName('TST-886431145-311111111101113')
    ->setSerialNumber('TST', 'TST', 'ed22f1d8-e6a2-1118-9b58-d9a8f11e445f')
    ->setOrganizationIdentifier('311111111101113')
    ->setOrganizationalUnitName('Riyadh Branch')
    ->setOrganizationName('ABCD Limited')
    ->setCountry('SA')
    ->setInvoiceType('1100')
    ->setAddress('RRRD2929')
    ->setBusinessCategory('Technology')
    ->generate();

echo 'CSR Generated via Facade' . PHP_EOL;

// Example 2: Request compliance certificate using the facade
$csr = $csrBuilder->getCsr();
$ccsid = $zatca->compliance()->requestComplianceCertificate(
    b64Csr: base64_encode($csr),
    otp: '123456'
);

echo 'Compliance CSID: ' . $ccsid->certificate . PHP_EOL;

// Example 3: Hash an invoice using the facade
$invoiceXml = file_get_contents('output/unsigned_simplified_invoice_standard.xml');
$hashingResult = $zatca->hashing()->hash($invoiceXml);

echo 'Invoice Hash: ' . $hashingResult->invoiceHash . PHP_EOL;

// Example 4: Sign an invoice using the facade
$privateKey = file_get_contents('output/private.pem');
$signingResult = $zatca->signing()->sign(
    csid: $ccsid,
    privateKeyContent: $privateKey,
    canonicalXml: base64_decode($hashingResult->b64CanonicalXml),
    invoiceHash: $hashingResult->invoiceHash
);

echo 'Signed Invoice: ' . $signingResult->b64SignedInvoice . PHP_EOL;