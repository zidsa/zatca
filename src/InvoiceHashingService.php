<?php

namespace Zid\Zatca;

use DOMDocument;
use DOMXPath;
use XSLTProcessor;
use Zid\Zatca\Entities\InvoiceHashingResult;
use Zid\Zatca\Exceptions\InvoiceHashingException;

class InvoiceHashingService
{
    public function hash(string $unsignedInvoiceXml): InvoiceHashingResult
    {
        $xmlDocument = new DOMDocument();
        $xmlDocument->preserveWhiteSpace = true;
        $xmlDocument->loadXML($unsignedInvoiceXml);

        $xslFilePath = __DIR__ . '/Data/xslfile.xsl';
        $xmlDeclaration = '<?xml version="1.0" encoding="utf-8"?>';

        // 1a. Get UUID from element <cbc:UUID>
        $xpath = new DOMXPath($xmlDocument);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $uuidNode = $xpath->query('//cbc:UUID')->item(0);

        if (!$uuidNode) {
            throw new InvoiceHashingException("UUID not found in the XML document.");
        }

        $uuid = $uuidNode->nodeValue;

        // Apply XSL transform
        $xsl = new DOMDocument();
        if (!$xsl->load($xslFilePath)) {
            throw new InvoiceHashingException("Failed to load XSL file: $xslFilePath");
        }

        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        // Transform document
        $transformedXml = $proc->transformToDoc($xmlDocument);
        if (!$transformedXml) {
            throw new InvoiceHashingException("XSL Transformation failed.");
        }

        // 3. Canonicalize (C14N) transformed document
        $canonicalXml = $transformedXml->C14N();  // C14N format

        // 4. Get byte hash256 from transformed document
        $hash = hash('sha256', $canonicalXml, true);  // result hash SHA-256 in binary data

        // 5. Encode hash to Base64
        $base64Hash = base64_encode($hash);

        // 6. Encode canonicalized XML to Base64
        $base64Invoice = base64_encode($xmlDeclaration . "\n" . $canonicalXml);

        return new InvoiceHashingResult(
            invoiceHash: $base64Hash,
            uuid: $uuid,
            b64Invoice: $base64Invoice,
            base64CanonicalXml: base64_encode($canonicalXml),
        );
    }
}
