<?php
require_once("../vendor/autoload.php");;

use Zid\Zatca\CertificateSigningRequestBuilder;

$zatcaCsrBuilder = new CertificateSigningRequestBuilder();

$zatcaCsrBuilder
    ->setCommonName('TST-886431145-311111111101113')
    ->setSerialNumber('1-TST|2-TST|3-ed22f1d8-e6a2-1118-9b58-d9a8f11e445f')
    ->setOrganizationIdentifier('311111111101113')
    ->setOrganizationalUnitName('Riyadh Branch')
    ->setOrganizationName('ABCD Limited')
    ->setCountry('SA')
    ->setInvoiceType('1100')
    ->setAddress('RRRD2929')
    ->setBusinessCategory('Technology')
    ->generate();

echo 'CSR:' . PHP_EOL;
echo $zatcaCsrBuilder->getCSR() . PHP_EOL;
echo 'Private Key:' . PHP_EOL;
echo $zatcaCsrBuilder->getPrivateKey() . PHP_EOL;

$zatcaCsrBuilder->saveCsr('output/certificate.csr');
$zatcaCsrBuilder->savePrivateKey('output/private.pem');

