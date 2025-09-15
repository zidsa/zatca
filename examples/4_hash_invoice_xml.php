<?php
require_once("../vendor/autoload.php");;

$xmlDocumentPath = 'output/unsigned_simplified_invoice_standard.xml';
$xmlDocument = new DOMDocument();
$xmlDocument->preserveWhiteSpace = true;
$xmlDocument->load($xmlDocumentPath);

$icv = 0;
$xslFilePath = '../src/Data/xslfile.xsl';
$ublTemplatePath = '../src/ZatcaDataUbl.xml';
$signaturePath = '../src/ZatcaDataSignature.xml';
$xmlDeclaration = '<?xml version="1.0" encoding="utf-8"?>';

// 1a. Get UUID from element <cbc:UUID>
$xpath = new DOMXPath($xmlDocument);
$xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
$uuidNode = $xpath->query('//cbc:UUID')->item(0);

if (!$uuidNode) {
    throw new Exception("UUID not found in the XML document.");
}

$uuid = $uuidNode->nodeValue;

// 1b. Check if it is a simplified invoice
$isSimplifiedInvoice = false;
$invoiceTypeCodeNode = $xpath->query('//cbc:InvoiceTypeCode')->item(0);
if ($invoiceTypeCodeNode) {
    $nameAttribute = $invoiceTypeCodeNode->getAttribute('name');
    $isSimplifiedInvoice = strpos($nameAttribute, '02') === 0;
}

// 2. Apply XSL transform
$xsl = new DOMDocument();
if (!$xsl->load($xslFilePath)) {
    throw new Exception("Failed to load XSL file: $xslFilePath");
}

$proc = new XSLTProcessor();
$proc->importStylesheet($xsl);

// Transform document
$transformedXml = $proc->transformToDoc($xmlDocument);
if (!$transformedXml) {
    throw new Exception("XSL Transformation failed.");
}

// 3. Canonicalize (C14N) transformed document
$canonicalXml = $transformedXml->C14N();  // C14N format

//echo $canonicalXml;

// 4. Get byte hash256 from transformed document
$hash = hash('sha256', $canonicalXml, true);  // result hash SHA-256 in binary data

// 5. Encode hash to Base64
$base64Hash = base64_encode($hash);

var_dump(['$base64Hash' => $base64Hash]);
// 6. Encode canonicalized XML to Base64
$base64Invoice = base64_encode($xmlDeclaration . "\n" . $canonicalXml);

$isSimplifiedInvoice = false; // TODO: Remove

// Return early for non-simplified invoices
//if (!$isSimplifiedInvoice) {
    $result = array(
        "invoiceHash" => $base64Hash,
        "uuid" => $uuid,
        "invoice" => $base64Invoice,
        'base64CanonicalXml' => base64_encode($canonicalXml),
    );
    var_dump($result);

    file_put_contents('output/simplified/invoice/invoice.json', json_encode($result, JSON_PRETTY_PRINT));
//}
