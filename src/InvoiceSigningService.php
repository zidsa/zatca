<?php

namespace Zid\Zatca;

use DateTime;
use Exception;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Entities\InvoiceSigningResult;
use Zid\Zatca\Exceptions\InvoiceSigningException;

class InvoiceSigningService
{
    public function __construct(private GetDigitalSignatureService $digitalSignatureService)
    {
    }

    public function sign(CSID $csid, string $privateKeyContent, string $qrCode, string $canonicalXml, string $invoiceUuid, $invoiceHash): InvoiceSigningResult
    {
        $ublTemplatePath = __DIR__ . '/Data/ZatcaDataUbl.xml';
        $signaturePath = __DIR__ . '/Data/ZatcaDataSignature.xml';

        $x509CertificateContent = base64_decode($csid->certificate);
        $privateKeyContent = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKeyContent);

        $xmlDeclaration = '<?xml version="1.0" encoding="utf-8"?>';

        // Signing Simplified Invoice Document
        $signatureTimestamp = (new DateTime())->format('Y-m-d\TH:i:s');

        // Generate public key hashing
        $hashBytes = hash('sha256', $x509CertificateContent, true);
        $hashHex = bin2hex($hashBytes);
        $publicKeyHashing = base64_encode($hashHex);

        // Parse the X.509 certificate
        $parsedCertificate = openssl_x509_read("-----BEGIN CERTIFICATE-----\n" .
            chunk_split($x509CertificateContent, 64, "\n") .
            "-----END CERTIFICATE-----\n");

        // Extract certificate information
        $certInfo = openssl_x509_parse($parsedCertificate);
        $issuerName = $this->getIssuerName($certInfo);
        $serialNumber = $this->getSerialNumberForCertificateObject($certInfo);
        $signedPropertiesHash = $this->getSignedPropertiesHash($signatureTimestamp, $publicKeyHashing, $issuerName, $serialNumber);
        $SignatureValue = $this->digitalSignatureService->get($invoiceHash, $privateKeyContent);

        // Populate UBLExtension Template
        $stringUBLExtension = file_get_contents($ublTemplatePath);
        $stringUBLExtension = str_replace("INVOICE_HASH", $invoiceHash, $stringUBLExtension);
        $stringUBLExtension = str_replace("SIGNED_PROPERTIES", $signedPropertiesHash, $stringUBLExtension);
        $stringUBLExtension = str_replace("SIGNATURE_VALUE", $SignatureValue, $stringUBLExtension);
        $stringUBLExtension = str_replace("CERTIFICATE_CONTENT", $x509CertificateContent, $stringUBLExtension);
        $stringUBLExtension = str_replace("SIGNATURE_TIMESTAMP", $signatureTimestamp, $stringUBLExtension);
        $stringUBLExtension = str_replace("PUBLICKEY_HASHING", $publicKeyHashing, $stringUBLExtension);
        $stringUBLExtension = str_replace("ISSUER_NAME", $issuerName, $stringUBLExtension);
        $stringUBLExtension = str_replace("SERIAL_NUMBER", $serialNumber, $stringUBLExtension);

        // Insert UBL into XML
        $insertPosition = strpos($canonicalXml, '>') + 1; // Find position after the first '>'
        $updatedXmlString = substr_replace($canonicalXml, $stringUBLExtension, $insertPosition, 0);

        // Load signature template content
        $stringSignature = file_get_contents($signaturePath);
        $stringSignature = str_replace("BASE64_QRCODE", $qrCode, $stringSignature);

        // Insert signature string before <cac:AccountingSupplierParty>
        $insertPositionSignature = strpos($updatedXmlString, '<cac:AccountingSupplierParty>'); // Find position of the opening tag
        if ($insertPositionSignature !== false) {
            $updatedXmlString = substr_replace($updatedXmlString, $stringSignature, $insertPositionSignature, 0);
        } else {
            throw new InvoiceSigningException("The <cac:AccountingSupplierParty> tag was not found in the XML.");
        }

        $base64Invoice = base64_encode($xmlDeclaration . "\n" . $updatedXmlString);

        return new InvoiceSigningResult(
            invoiceHash: $invoiceHash,
            b64SignedInvoice: $base64Invoice,
            uuid: $invoiceUuid
        );
    }

    private function getIssuerName($certInfo) {
        $issuer = $certInfo['issuer'];

        if (isset($issuer['DC']) && is_array($issuer['DC'])) {
            $issuer['DC'] = array_reverse($issuer['DC']);
        }

        $issuerNameParts = [];
        if (!empty($issuer['CN'])) {
            $issuerNameParts[] = "CN=" . $issuer['CN'];
        }

        if (!empty($issuer['DC']) && is_array($issuer['DC'])) {
            foreach ($issuer['DC'] as $dc) {
                if (!empty($dc)) {
                    $issuerNameParts[] = "DC=" . $dc;
                }
            }
        }

        return implode(", ", $issuerNameParts);
    }

    private function getSerialNumberForCertificateObject($certInfo) {
        $serialNumberHex = $certInfo['serialNumberHex'];

        $serialNumberDec = '0';
        $hexLength = strlen($serialNumberHex);
        for ($i = 0; $i < $hexLength; $i++) {
            $hexDigit = hexdec($serialNumberHex[$i]);
            $serialNumberDec = bcmul($serialNumberDec, '16', 0);
            $serialNumberDec = bcadd($serialNumberDec, $hexDigit, 0);
        }

        return $serialNumberDec;
    }

    private function getSignedPropertiesHash($signingTime, $digestValue, $x509IssuerName, $x509SerialNumber) {

        // Construct the XML string with exactly 36 spaces in front of <xades:SignedSignatureProperties>
        $xmlString = '<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">' . "\n" .
            '                                    <xades:SignedSignatureProperties>' . "\n" .
            '                                        <xades:SigningTime>' . $signingTime . '</xades:SigningTime>' . "\n" .
            '                                        <xades:SigningCertificate>' . "\n" .
            '                                            <xades:Cert>' . "\n" .
            '                                                <xades:CertDigest>' . "\n" .
            '                                                    <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' . "\n" .
            '                                                    <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $digestValue . '</ds:DigestValue>' . "\n" .
            '                                                </xades:CertDigest>' . "\n" .
            '                                                <xades:IssuerSerial>' . "\n" .
            '                                                    <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $x509IssuerName . '</ds:X509IssuerName>' . "\n" .
            '                                                    <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $x509SerialNumber . '</ds:X509SerialNumber>' . "\n" .
            '                                                </xades:IssuerSerial>' . "\n" .
            '                                            </xades:Cert>' . "\n" .
            '                                        </xades:SigningCertificate>' . "\n" .
            '                                    </xades:SignedSignatureProperties>' . "\n" .
            '                                </xades:SignedProperties>';

        $xmlString = str_replace("\r\n", "\n", $xmlString);
        $xmlString = trim($xmlString);

        $hashBytes = hash('sha256', $xmlString, true);

        $hashHex = bin2hex($hashBytes);

        return base64_encode($hashHex);
    }
}
