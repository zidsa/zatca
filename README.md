<div align="center">

# 🧾 ZATCA E-Invoicing Phase 2

### PHP Package for Saudi Arabia's ZATCA (Fatoora) Integration

Simplifies Phase 2 e-invoicing requirements including certificate generation, invoice signing, QR code generation, and submission to ZATCA's API

[View Examples](https://github.com/zidsa/zatca/tree/master/examples) • [Report a Bug](https://github.com/zidsa/zatca/issues)

---

</div>

## Features

- **Certificate Management**: Generate CSR (Certificate Signing Request) and obtain compliance/production certificates
- **Invoice Processing**: Hash and sign XML invoices according to ZATCA specifications
- **QR Code Generation**: Create compliant QR codes for both simplified and standard invoices
- **Compliance Validation**: Check invoice compliance before production submission
- **Invoice Submission**: Submit invoices to ZATCA via Reporting (simplified) or Clearance (standard) APIs
- **Multi-Environment Support**: Sandbox, Simulation, and Production environments
- **Clean Architecture**: Well-structured, maintainable, and testable code

## Requirements

- PHP 8.1 or higher
- Required PHP extensions:
  - `ext-openssl`
  - `ext-dom`
  - `ext-xsl`
  - `ext-json`
  - `ext-bcmath`
  - `ext-simplexml`

## Installation

Install the package via Composer:

```bash
composer require zid/zatca
```

## Quick Start

### 1. Generate Certificate Signing Request (CSR)

First, generate a CSR and private key for your organization:

```php
<?php
require_once 'vendor/autoload.php';

use Zid\Zatca\CertificateSigningRequestBuilder;

$csrBuilder = new CertificateSigningRequestBuilder();

$csrBuilder
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

// Save CSR and private key
$csrBuilder->saveCsr('certificate.csr');
$csrBuilder->savePrivateKey('private.pem');
```

### 2. Request Compliance Certificate

Obtain a compliance certificate from ZATCA using your CSR and OTP:

```php
<?php
use Zid\Zatca\ComplianceService;
use Zid\Zatca\Enums\ZatcaEnvironment;

$complianceService = new ComplianceService(ZatcaEnvironment::SANDBOX);

$csr = file_get_contents('certificate.csr');
$ccsid = $complianceService->requestComplianceCertificate(
    b64Csr: base64_encode($csr),
    otp: '123456'
);

// Save compliance certificate
$ccsid->saveAsJson('ccsid.json');

echo "Certificate: " . $ccsid->certificate . PHP_EOL;
echo "Secret: " . $ccsid->secret . PHP_EOL;
```

### 3. Hash Invoice XML

Hash your unsigned invoice XML:

```php
<?php
use Zid\Zatca\InvoiceHashingService;

$xmlContent = file_get_contents('unsigned_invoice.xml');

$hashingService = new InvoiceHashingService();
$result = $hashingService->hash($xmlContent);

echo "Invoice Hash: " . $result->invoiceHash . PHP_EOL;
echo "UUID: " . $result->uuid . PHP_EOL;

// Save for later use
file_put_contents('invoice.json', json_encode($result, JSON_PRETTY_PRINT));
```

### 4. Sign Invoice

Sign the invoice with your private key and compliance certificate:

```php
<?php
use Zid\Zatca\InvoiceSigningService;
use Zid\Zatca\GetDigitalSignatureService;
use Zid\Zatca\GetPublicKeyAndSignatureService;
use Zid\Zatca\QrCodeGeneratorService;
use Zid\Zatca\Entities\CSID;

$invoiceData = json_decode(file_get_contents('invoice.json'), true);
$canonicalXml = base64_decode($invoiceData['base64CanonicalXml']);
$invoiceHash = $invoiceData['invoiceHash'];

$ccsid = CSID::loadFromJson('ccsid.json');
$privateKey = file_get_contents('private.pem');
$privateKey = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKey);

$digitalSignatureService = new GetDigitalSignatureService();
$publicKeyService = new GetPublicKeyAndSignatureService();
$qrCodeService = new QrCodeGeneratorService($publicKeyService);

$signingService = new InvoiceSigningService($digitalSignatureService, $qrCodeService);

$result = $signingService->sign(
    csid: $ccsid,
    privateKeyContent: $privateKey,
    canonicalXml: $canonicalXml,
    invoiceHash: $invoiceHash
);

echo "Signed Invoice (Base64): " . $result->b64SignedInvoice . PHP_EOL;
echo "QR Code (Base64): " . $result->b64QrCode . PHP_EOL;
```

### 5. Check Compliance

Validate your signed invoice against ZATCA's compliance checks:

```php
<?php
use Zid\Zatca\ComplianceService;
use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Entities\CSID;

$complianceService = new ComplianceService(ZatcaEnvironment::SANDBOX);
$ccsid = CSID::loadFromJson('ccsid.json');

$result = $complianceService->checkCompliance(
    binarySecurityToken: $ccsid->certificate,
    secret: $ccsid->secret,
    invoiceHash: 'YOUR_INVOICE_HASH',
    invoiceUuid: 'YOUR_INVOICE_UUID',
    signedInvoice: 'BASE64_SIGNED_INVOICE'
);

echo "Validation Status: " . $result->validationResults->status . PHP_EOL;
echo "Clearance Status: " . $result->clearanceStatus . PHP_EOL;

if (!empty($result->validationResults->errorMessages)) {
    echo "Errors:" . PHP_EOL;
    print_r($result->validationResults->errorMessages);
}
```

### 6. Generate Production Certificate

After successful compliance validation, request a production certificate:

```php
<?php
use Zid\Zatca\ProductionCsidGeneratorService;
use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Entities\CSID;

$productionService = new ProductionCsidGeneratorService(ZatcaEnvironment::SANDBOX);
$ccsid = CSID::loadFromJson('ccsid.json');

$pcsid = $productionService->requestProductionCertificate(
    binarySecurityToken: $ccsid->certificate,
    secret: $ccsid->secret,
    ccsidRequestId: $ccsid->requestId
);

// Save production certificate
$pcsid->saveAsJson('pcsid.json');
```

### 7. Submit Invoice to ZATCA

Submit your signed invoice to ZATCA (Reporting for simplified, Clearance for standard):

```php
<?php
use Zid\Zatca\InvoiceSubmissionService;
use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Entities\CSID;

$submissionService = new InvoiceSubmissionService(ZatcaEnvironment::PRODUCTION);
$pcsid = CSID::loadFromJson('pcsid.json');

$response = $submissionService->submit(
    csid: $pcsid,
    isSimplified: true, // false for standard invoices
    invoiceHash: 'YOUR_INVOICE_HASH',
    invoiceUuid: 'YOUR_INVOICE_UUID',
    invoiceXml: 'YOUR_SIGNED_INVOICE_XML'
);

if ($response->isSubmitted) {
    echo "Invoice submitted successfully!" . PHP_EOL;
    echo "Status: " . $response->status . PHP_EOL;
} else {
    echo "Submission failed!" . PHP_EOL;
    print_r($response->validationResults->errorMessages);
}
```

## Environment Configuration

The package supports three ZATCA environments:

```php
use Zid\Zatca\Enums\ZatcaEnvironment;

// Sandbox - For development and testing
ZatcaEnvironment::SANDBOX

// Simulation - For pre-production testing
ZatcaEnvironment::SIMULATION

// Production - For live invoices
ZatcaEnvironment::PRODUCTION
```

## Using the Facade (Alternative Approach)

The `Zatca` facade provides a cleaner, more convenient API for accessing all services. Instead of manually instantiating services and their dependencies, you can use the facade as a single entry point.

### Benefits of Using the Facade

- **Simplified API**: Single entry point for all ZATCA operations
- **Lazy Loading**: Services are only instantiated when needed
- **Dependency Management**: Automatically handles service dependencies
- **Cleaner Code**: Less boilerplate, more readable

### Facade Example: Complete Workflow

```php
<?php
require_once 'vendor/autoload.php';

use Zid\Zatca\Zatca;
use Zid\Zatca\Enums\ZatcaEnvironment;

// Initialize the facade
$zatca = new Zatca(ZatcaEnvironment::SANDBOX);

// 1. Generate CSR
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

$csrBuilder->saveCsr('certificate.csr');
$csrBuilder->savePrivateKey('private.pem');

// 2. Request compliance certificate
$csr = file_get_contents('certificate.csr');
$ccsid = $zatca->compliance()->requestComplianceCertificate(
    b64Csr: base64_encode($csr),
    otp: '123456'
);
$ccsid->saveAsJson('ccsid.json');

// 3. Hash invoice
$invoiceXml = file_get_contents('unsigned_invoice.xml');
$hashingResult = $zatca->hashing()->hash($invoiceXml);

// 4. Sign invoice
$privateKey = file_get_contents('private.pem');
$privateKey = str_replace(["\n", "\t", "-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----"], '', $privateKey);

$signingResult = $zatca->signing()->sign(
    csid: $ccsid,
    privateKeyContent: $privateKey,
    canonicalXml: base64_decode($hashingResult->b64CanonicalXml),
    invoiceHash: $hashingResult->invoiceHash
);

// 5. Check compliance
$validationResponse = $zatca->compliance()->checkCompliance(
    binarySecurityToken: $ccsid->certificate,
    secret: $ccsid->secret,
    invoiceHash: $hashingResult->invoiceHash,
    invoiceUuid: $hashingResult->uuid,
    signedInvoice: $signingResult->b64SignedInvoice
);

if ($validationResponse->validationResults->status === 'PASS') {
    // 6. Request production certificate
    $pcsid = $zatca->production()->requestProductionCertificate(
        binarySecurityToken: $ccsid->certificate,
        secret: $ccsid->secret,
        ccsidRequestId: $ccsid->requestId
    );
    $pcsid->saveAsJson('pcsid.json');
    
    // 7. Submit invoice to production
    $submissionResult = $zatca->submission()->submit(
        csid: $pcsid,
        isSimplified: true,
        invoiceHash: $hashingResult->invoiceHash,
        invoiceUuid: $hashingResult->uuid,
        invoiceXml: base64_decode($signingResult->b64SignedInvoice)
    );
    
    if ($submissionResult->isSubmitted) {
        echo "Invoice submitted successfully!" . PHP_EOL;
    }
}
```

### Facade API Methods

The `Zatca` facade provides the following methods:

```php
// Access services
$zatca->compliance()    // ComplianceService
$zatca->production()    // ProductionCsidGeneratorService
$zatca->submission()    // InvoiceSubmissionService
$zatca->hashing()       // InvoiceHashingService
$zatca->signing()       // InvoiceSigningService
$zatca->qrCode()        // QrCodeGeneratorService
$zatca->csrBuilder()    // CertificateSigningRequestBuilder
$zatca->client()        // ZatcaClient (for direct API access)
```

### Comparison: Traditional vs Facade

**Traditional Approach:**
```php
// Manual dependency injection
$digitalSignatureService = new GetDigitalSignatureService();
$publicKeyService = new GetPublicKeyAndSignatureService();
$qrCodeService = new QrCodeGeneratorService($publicKeyService);
$signingService = new InvoiceSigningService($digitalSignatureService, $qrCodeService);

$result = $signingService->sign(...);
```

**Facade Approach:**
```php
// Clean and simple
$zatca = new Zatca(ZatcaEnvironment::SANDBOX);
$result = $zatca->signing()->sign(...);
```

The facade handles all dependency injection automatically, making your code cleaner and easier to maintain.

## Invoice Types

When generating CSR, specify the invoice type using a 4-digit code:

- `1100` - Standard & Simplified Invoices
- `0100` - Simplified Invoice Only (B2C)
- `1000` - Standard Invoice Only (B2B)
Each digit acts as a boolean flag: `[Standard, Simplified, Future Use, Future Use]`

## API Reference

### Core Services

#### CertificateSigningRequestBuilder
Generates CSR and private key for ZATCA onboarding.

**Methods:**
- `setCommonName(string $name)` - Set common name
- `setSerialNumber(string $solutionProvider, string $solutionName, string $serialNumber)` - Set device serial number
- `setOrganizationIdentifier(string $id)` - Set organization tax ID
- `setOrganizationalUnitName(string $name)` - Set organizational unit
- `setOrganizationName(string $name)` - Set organization name
- `setCountry(string $country)` - Set country code (SA)
- `setInvoiceType(string $type)` - Set invoice type (e.g., '1100')
- `setAddress(string $address)` - Set business address
- `setBusinessCategory(string $category)` - Set business category
- `generate()` - Generate CSR and private key
- `getCsr()` - Get generated CSR
- `getPrivateKey()` - Get generated private key
- `saveCsr(string $path)` - Save CSR to file
- `savePrivateKey(string $path)` - Save private key to file

#### ComplianceService
Handles compliance certificate requests and validation.

**Methods:**
- `requestComplianceCertificate(string $b64Csr, string $otp): CSID`
- `checkCompliance(string $binarySecurityToken, string $secret, string $invoiceHash, string $invoiceUuid, string $signedInvoice): ValidationResponse`

#### InvoiceHashingService
Hashes invoice XML according to ZATCA specifications.

**Methods:**
- `hash(string $unsignedInvoiceXml): InvoiceHashingResult`

#### InvoiceSigningService
Signs invoices with digital signature and generates QR codes.

**Methods:**
- `sign(CSID $csid, string $privateKeyContent, string $canonicalXml, string $invoiceHash): InvoiceSigningResult`

#### QrCodeGeneratorService
Generates ZATCA-compliant QR codes.

**Methods:**
- `generate(CSID $csid, string $invoiceHash, string $canonicalXml, string $signatureValue): string`

#### ProductionCsidGeneratorService
Requests production certificates after compliance validation.

**Methods:**
- `requestProductionCertificate(string $binarySecurityToken, string $secret, string $ccsidRequestId): CSID`

#### InvoiceSubmissionService
Submits invoices to ZATCA via Reporting or Clearance APIs.

**Methods:**
- `submit(CSID $csid, bool $isSimplified, string $invoiceHash, string $invoiceUuid, string $invoiceXml): SubmissionResponse`

### Entities

#### CSID
Represents a Certificate Signing ID (compliance or production certificate).

**Properties:**
- `string $certificate` - Base64 encoded certificate
- `string $secret` - Certificate secret
- `string $requestId` - Request ID from ZATCA

**Methods:**
- `static loadFromJson(string $filepath): self`
- `saveAsJson(string $filepath): void`

#### InvoiceHashingResult
Result of invoice hashing operation.

**Properties:**
- `string $invoiceHash` - Base64 encoded SHA-256 hash
- `string $uuid` - Invoice UUID
- `string $b64Invoice` - Base64 encoded invoice
- `string $b64CanonicalXml` - Base64 encoded canonical XML

#### InvoiceSigningResult
Result of invoice signing operation.

**Properties:**
- `string $signature` - Digital signature
- `string $b64SignedInvoice` - Base64 encoded signed invoice
- `string $b64QrCode` - Base64 encoded QR code

#### SubmissionResponse
Response from invoice submission to ZATCA.

**Properties:**
- `ValidationResults $validationResults` - Validation messages
- `string $status` - Submission status (REPORTED/CLEARED)
- `bool $isSubmitted` - Whether submission was successful

#### ValidationResponse
Response from compliance validation.

**Properties:**
- `ValidationResults $validationResults` - Validation messages
- `string|null $reportingStatus` - Reporting status
- `string|null $clearanceStatus` - Clearance status
- `string|null $qrSellerStatus` - QR seller status
- `string|null $qrBuyerStatus` - QR buyer status

#### ValidationResults
Validation messages from ZATCA.

**Properties:**
- `array $infoMessages` - Informational messages
- `array $warningMessages` - Warning messages
- `array $errorMessages` - Error messages
- `string $status` - Overall validation status

## Examples

Complete working examples are available in the `examples/` directory:

1. `0_using_facade.php` - **Using the Zatca Facade (Recommended)**
2. `1_generate_csr.php` - Generate CSR and private key
3. `2_generate_compliance_csid.php` - Request compliance certificate
4. `3_hash_invoice_xml.php` - Hash invoice XML
5. `4_generate_qr_code_xml.php` - Generate QR code
6. `5_sign_invoice_xml.php` - Sign invoice
7. `6_check_compliance.php` - Validate compliance
8. `7_generate_production_csid.php` - Request production certificate

## Testing

Run the test suite:

```bash
composer test
```

Or use PHPUnit directly:

```bash
vendor/bin/phpunit
```

## Error Handling

The package throws specific exceptions for different error scenarios:

```php
use Zid\Zatca\Exceptions\ZatcaApiException;
use Zid\Zatca\Exceptions\InvoiceHashingException;
use Zid\Zatca\Exceptions\QrGenerationException;

try {
    // Your code here
} catch (ZatcaApiException $e) {
    // Handle API errors
    echo "API Error: " . $e->getMessage();
} catch (InvoiceHashingException $e) {
    // Handle hashing errors
    echo "Hashing Error: " . $e->getMessage();
} catch (QrGenerationException $e) {
    // Handle QR generation errors
    echo "QR Error: " . $e->getMessage();
}
```

## Best Practices

1. **Store Certificates Securely**: Keep your private keys and certificates in secure storage
2. **Use Environment Variables**: Store sensitive data like OTPs and secrets in environment variables
3. **Validate Before Submission**: Always check compliance before submitting to production
4. **Handle Errors Gracefully**: Implement proper error handling and logging
5. **Test in Sandbox**: Thoroughly test your integration in the sandbox environment
6. **Keep Certificates Updated**: Monitor certificate expiration and renew as needed

## ZATCA Integration Workflow

```
1. Generate CSR + Private Key
   ↓
2. Request Compliance Certificate (with OTP)
   ↓
3. Hash Invoice XML
   ↓
4. Sign Invoice
   ↓
5. Generate QR Code
   ↓
6. Check Compliance
   ↓
7. Request Production Certificate
   ↓
8. Submit Invoices to Production
```

## Resources

- [ZATCA E-Invoicing Portal](https://zatca.gov.sa/en/E-Invoicing/Pages/default.aspx)
- [ZATCA Developer Portal](https://sandbox.zatca.gov.sa/)
- [E-Invoicing Technical Documentation](https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Documents/E-Invoicing_Detailed__Guideline.pdf)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## Credits

Developed and maintained by [Zid](https://zid.sa)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/zidsa/zatca).

---

**Disclaimer**: This is an unofficial package and is not affiliated with or endorsed by ZATCA. Use at your own risk and ensure compliance with all ZATCA regulations.
