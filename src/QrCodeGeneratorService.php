<?php

declare(strict_types=1);

namespace Zid\Zatca;

use Exception;
use SimpleXMLElement;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Exceptions\QrGenerationException;

class QrCodeGeneratorService
{
    private SimpleXMLElement $xmlObject;

    public function __construct(
        private GetPublicKeyAndSignatureService $publicKeyAndSignatureService,
    ) {
    }

    public function generate(CSID $csid, string $invoiceHash, string $canonicalXml, string $signatureValue): string
    {
        $x509CertificateContent = base64_decode($csid->certificate);
        try {
            $result = $this->publicKeyAndSignatureService->get($x509CertificateContent);
            $ECSDAPublicKey = $result['public_key_raw'];

            $this->loadXmlObject($canonicalXml);
            $qrCodeTags = $this->getInvoiceDetails($invoiceHash, $signatureValue, $ECSDAPublicKey);

            // Only add certificateSignature if simplified invoice
            if ($this->isSimplifiedInvoice()) {
                $qrCodeTags[9] = $result['signature'];
            }

            return $this->generateQRCodeFromTagsValues($qrCodeTags);
        } catch (Exception $exception) {
            throw new QrGenerationException("Error in generating QR Code for the EInvoice", 0, $exception);
        }
    }

    private function getInvoiceDetails(string $invoiceHash, string $ECSDASignature, string $ECSDAPublicKey): array
    {
        if (!isset($this->xmlObject)) {
            throw new QrGenerationException('XML object is not set');
        }

        // Extracting values using XPath
        $sellerName = (string)$this->xmlObject->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')[0];
        $sellerVat = (string)$this->xmlObject->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID')[0];
        $invoiceTimestamp = $this->xmlObject->xpath('//cbc:IssueDate')[0] . 'T' . $this->xmlObject->xpath('//cbc:IssueTime')[0];
        $invoiceTotalWithVat = (string)$this->xmlObject->xpath('//cac:LegalMonetaryTotal/cbc:PayableAmount')[0];
        $vatTotal = (string)$this->xmlObject->xpath('//cac:TaxTotal/cbc:TaxAmount')[0];

        return [
            1 => $sellerName,
            2 => $sellerVat,
            3 => $invoiceTimestamp,
            4 => $invoiceTotalWithVat,
            5 => $vatTotal,
            6 => $invoiceHash,
            7 => $ECSDASignature,
            8 => $ECSDAPublicKey,
        ];
    }

    private function isSimplifiedInvoice(): bool
    {
        if (!isset($this->xmlObject)) {
            throw new QrGenerationException('XML object is not set');
        }

        // Extract InvoiceTypeCode Name
        $invoiceTypeCodeName = (string)$this->xmlObject->xpath('//cbc:InvoiceTypeCode/@name')[0];

        return str_starts_with($invoiceTypeCodeName, '02');
    }

    private function generateQRCodeFromTagsValues($invoiceDetails): string
    {
        $data = '';
        foreach ($invoiceDetails as $key => $value) {
            $data .= $this->getTLV($key, $value);
        }
        return base64_encode($data);
    }

    /**
     * @throws QrGenerationException
     */
    private function getTLV(int $tag, string $value): string
    {
        $t = $this->getTag($tag);
        $l = $this->getLength($value);
        $v = $value;

        return $t . $l . $v;
    }

    private function getLength(?string $value): string
    {
        if ($value === null) {
            return chr(0x80);
        }

        $length =  strlen($value);

        if ($length > 127) {
            throw new QrGenerationException('Tag value is too long');
        }

        return chr($length);
    }

    private function getTag(int $tag): string
    {
        if (($tag < 1) || ($tag > 9)) {
            throw new QrGenerationException('Invalid QR tag key provided');
        }

        // Convert int to hex
        return chr($tag);
    }

    private function loadXmlObject(string $xml): void
    {
        // Load XML into SimpleXMLElement
        $this->xmlObject = new SimpleXMLElement($xml);

        // Register namespaces
        $this->xmlObject->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $this->xmlObject->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
    }
}
