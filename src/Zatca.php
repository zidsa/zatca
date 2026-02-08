<?php

declare(strict_types=1);

namespace Zid\Zatca;

use Zid\Zatca\API\ZatcaClient;
use Zid\Zatca\Enums\ZatcaEnvironment;

/**
 * Zatca Facade - Main entry point for ZATCA E-Invoicing operations
 * 
 * Provides convenient access to all ZATCA services:
 * - Certificate generation (CSR, Compliance CSID, Production CSID)
 * - Invoice operations (hashing, signing, QR code generation)
 * - Invoice submission (compliance check, reporting, clearance)
 */
class Zatca
{
    private ZatcaClient $client;
    private ?ComplianceService $complianceService = null;
    private ?ProductionCsidGeneratorService $productionCsidService = null;
    private ?InvoiceSubmissionService $submissionService = null;
    private ?InvoiceHashingService $hashingService = null;
    private ?InvoiceSigningService $signingService = null;
    private ?QrCodeGeneratorService $qrCodeService = null;

    public function __construct(
        private ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
        ?ZatcaClient $client = null
    ) {
        $this->client = $client ?? new ZatcaClient($this->environment);
    }

    /**
     * Get the ZatcaClient instance for direct API access
     */
    public function client(): ZatcaClient
    {
        return $this->client;
    }

    /**
     * Get ComplianceService for compliance certificate operations
     */
    public function compliance(): ComplianceService
    {
        if ($this->complianceService === null) {
            $this->complianceService = new ComplianceService($this->environment);
        }
        
        return $this->complianceService;
    }

    /**
     * Get ProductionCsidGeneratorService for production certificate operations
     */
    public function production(): ProductionCsidGeneratorService
    {
        if ($this->productionCsidService === null) {
            $this->productionCsidService = new ProductionCsidGeneratorService($this->environment);
        }
        
        return $this->productionCsidService;
    }

    /**
     * Get InvoiceSubmissionService for submitting invoices
     */
    public function submission(): InvoiceSubmissionService
    {
        if ($this->submissionService === null) {
            $this->submissionService = new InvoiceSubmissionService($this->environment);
        }
        
        return $this->submissionService;
    }

    /**
     * Get InvoiceHashingService for hashing invoices
     */
    public function hashing(): InvoiceHashingService
    {
        if ($this->hashingService === null) {
            $this->hashingService = new InvoiceHashingService();
        }
        
        return $this->hashingService;
    }

    /**
     * Get InvoiceSigningService for signing invoices
     */
    public function signing(): InvoiceSigningService
    {
        if ($this->signingService === null) {
            $digitalSignatureService = new GetDigitalSignatureService();
            $publicKeyService = new GetPublicKeyAndSignatureService();
            $qrCodeService = new QrCodeGeneratorService($publicKeyService);
            
            $this->signingService = new InvoiceSigningService($digitalSignatureService, $qrCodeService);
        }
        
        return $this->signingService;
    }

    /**
     * Get QrCodeGeneratorService for generating QR codes
     */
    public function qrCode(): QrCodeGeneratorService
    {
        if ($this->qrCodeService === null) {
            $publicKeyService = new GetPublicKeyAndSignatureService();
            $this->qrCodeService = new QrCodeGeneratorService($publicKeyService);
        }
        
        return $this->qrCodeService;
    }

    /**
     * Create a new CSR builder instance
     */
    public function csrBuilder(): CertificateSigningRequestBuilder
    {
        return new CertificateSigningRequestBuilder();
    }
}
