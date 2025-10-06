<?php

namespace Zid\Zatca;

use Zid\Zatca\API\ZatcaClient;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Entities\ValidationResponse;
use Zid\Zatca\Entities\ValidationResults;
use Zid\Zatca\Enums\ZatcaEnvironment;

class ComplianceService
{
    private ZatcaClient $zatcaClient;

    public function __construct(
        ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
    ) {
        $this->zatcaClient = new ZatcaClient($environment);
    }

    public function requestComplianceCertificate(string $csr, string $otp): CSID
    {
        $result = $this->zatcaClient->complianceApi()->requestComplianceCertificate($csr, $otp);

        return new CSID(
            certificate: $result['binarySecurityToken'],
            secret: $result['secret'],
            requestId: $result['requestID'],
        );
    }

    /**
     * @param string $binarySecurityToken Compliance Certificate
     * @param string $secret Compliance Certificate Secret
     * @param string $invoiceHash Invoice Hash
     * @param string $invoiceUuid UUID of the invoice
     * @param string $signedInvoice Base64 encoded signed invoice
     * @return ValidationResponse
     */
    public function checkCompliance(string $binarySecurityToken, string $secret, string $invoiceHash, string $invoiceUuid, string $signedInvoice): ValidationResponse
    {
        $result = $this->zatcaClient->complianceApi()->checkCompliance($binarySecurityToken, $secret, $invoiceHash, $invoiceUuid, $signedInvoice);

        return new ValidationResponse(
            validationResults: new ValidationResults(
                infoMessages: $result['validationResults']['infoMessages'],
                warningMessages: $result['validationResults']['warningMessages'],
                errorMessages: $result['validationResults']['errorMessages'],
                status: $result['validationResults']['status'],
            ),
            reportingStatus: $result['reportingStatus'],
            clearanceStatus: $result['clearanceStatus'],
            qrSellerStatus: $result['qrSellertStatus'],
            qrBuyerStatus: $result['qrBuyertStatus'],
        );
    }
}
