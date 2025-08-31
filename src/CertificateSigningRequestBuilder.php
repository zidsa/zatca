<?php

namespace Zid\Zatca;

use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Exceptions\CsrGenerationException;

class CertificateSigningRequestBuilder
{
    private string $organizationIdentifier;
    private string $serialNumber;
    private string $commonName = '';
    private string $country = 'SA';
    private string $organizationName = '';
    private string $organizationalUnitName = '';
    private string $address = '';
    private int $invoiceType = 1100;
    private string $businessCategory = '';
    private ?\OpenSSLAsymmetricKey $privateKey = null;
    private ?\OpenSSLCertificateSigningRequest $csr = null;
    private ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX;

    public function setOrganizationIdentifier(string $organizationIdentifier): self
    {
        if (!preg_match('/^3\d{13}3$/', $organizationIdentifier)) {
            throw new CsrGenerationException('Organization Identifier must be 15 digits, starting with 3 and ending with 3.');
        }

        $this->organizationIdentifier = $organizationIdentifier;
        return $this;
    }

    public function setSerialNumber(string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function setCommonName(string $commonName): self
    {
        $this->commonName = $commonName;
        return $this;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function setOrganizationName(string $organizationName): self
    {
        $this->organizationName = $organizationName;
        return $this;
    }

    public function setOrganizationalUnitName(string $organizationalUnitName): self
    {
        $this->organizationalUnitName = $organizationalUnitName;
        return $this;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function setInvoiceType(int $invoiceType): self
    {
        $this->invoiceType = $invoiceType;
        return $this;
    }

    public function setEnvironment(ZatcaEnvironment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function setBusinessCategory(string $businessCategory): self
    {
        $this->businessCategory = $businessCategory;
        return $this;
    }

    private function getOpenSslConfig(): array
    {
        return [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => "secp256k1",
            "req_extensions" => "v3_req",
        ];
    }

    /**
     * @throws CsrGenerationException
     */
    private function generatePrivateKey(): void
    {
        $this->privateKey = openssl_pkey_new($this->getOpenSslConfig());

        if ($this->privateKey === false) {
            throw new CsrGenerationException('Failed to generate private key: ' . openssl_error_string());
        }
    }

    private function generateCsrConfigFile(): string
    {
        $configTemplate = file_get_contents(__DIR__ . '/Data/csr_template.txt');

        $configKeyValue = [
            'REPLACE_WITH_COUNTRY_NAME' => $this->country,
            'REPLACE_WITH_ORGANIZATION_UNIT_NAME' => $this->organizationalUnitName,
            'REPLACE_WITH_ORGANIZATION_NAME' => $this->organizationName,
            'REPLACE_WITH_COMMON_NAME' => $this->commonName,
            'REPLACE_WITH_ASN_TEMPLATE' => $this->getAsnTemplate(),
            'REPLACE_WITH_SERIAL_NUMBER' => $this->serialNumber,
            'REPLACE_WITH_ORGANIZATION_IDENTIFIER' => $this->organizationIdentifier,
            'REPLACE_WITH_INVOICE_TYPE' => (string) $this->invoiceType,
            'REPLACE_WITH_LOCATION_ADDRESS' => $this->address,
            'REPLACE_WITH_BUSINESS_CATEGORY' => $this->businessCategory,
        ];

        $configContent = str_replace(array_keys($configKeyValue), array_values($configKeyValue), $configTemplate);

        $tempFilepath = tempnam(sys_get_temp_dir(), 'zatca_csr_conf_');
        file_put_contents($tempFilepath, $configContent);

        return $tempFilepath;
    }


    private function getAsnTemplate(): string
    {
        return match($this->environment) {
            ZatcaEnvironment::PRODUCTION => 'ZATCA-Code-Signing',
            ZatcaEnvironment::SIMULATION => 'PREZATCA-Code-Signing',
            ZatcaEnvironment::SANDBOX => 'TSTZATCA-Code-Signing',
        };
    }

    /**
     * @throws CsrGenerationException
     */
    public function generate(): void
    {
        $distinguishedNames = [
            "commonName" => $this->commonName,
            "organizationName" => $this->organizationName,
            "organizationalUnitName" => $this->organizationalUnitName,
            "countryName" => $this->country
        ];

        $this->generatePrivateKey();

        $csrConfig = [
            "config" => $this->generateCsrConfigFile(),
            "digest_alg" => "sha256",
        ];

        $this->csr = openssl_csr_new($distinguishedNames, $this->privateKey, $csrConfig);
        if ($this->csr === false) {
            throw new CsrGenerationException('CSR generation failed: ' . openssl_error_string());
        }
    }

    public function getCsr(): string
    {
        if ($this->csr === null) {
            throw new CsrGenerationException('CSR is not set. Generate it first.');
        }

        if (!openssl_csr_export($this->csr, $csr)) {
            throw new CsrGenerationException('CSR export failed: ' . openssl_error_string());
        }

        return $csr;
    }

    public function saveCsr(string $filepath): void
    {
        if ($this->csr === null) {
            throw new CsrGenerationException('CSR is not set. Generate it first.');
        }

        if (!openssl_csr_export_to_file($this->csr, $filepath)) {
            throw new CsrGenerationException('CSR export failed: ' . openssl_error_string());
        }
    }

    public function getPrivateKey(): string
    {
        if ($this->privateKey === null) {
            throw new CsrGenerationException('Private Key is not set. Generate it first.');
        }

        if (!openssl_pkey_export($this->privateKey, $pkey)) {
            throw new CsrGenerationException('Private key export failed: ' . openssl_error_string());
        }

        return $pkey;
    }

    public function savePrivateKey(string $filepath): void
    {
        if ($this->privateKey === null) {
            throw new CsrGenerationException('Private Key is not set. Generate it first.');
        }

        if (!openssl_pkey_export_to_file($this->privateKey, $filepath)) {
            throw new CsrGenerationException('Private key export failed: ' . openssl_error_string());
        }
    }
}
