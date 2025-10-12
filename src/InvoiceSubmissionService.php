<?php

namespace Zid\Zatca;

use Zid\Zatca\API\ZatcaClient;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Entities\SubmissionResponse;
use Zid\Zatca\Entities\ValidationResults;

class InvoiceSubmissionService
{
    public function __construct(private ZatcaClient $zatcaClient) {
    }

    public function submit(
        CSID $csid,
        bool $isSimplified,
        string $invoiceHash,
        string $invoiceUuid,
        string $invoiceXml,
    ): SubmissionResponse
    {
        $api = $isSimplified ? $this->zatcaClient->reportingApi() : $this->zatcaClient->clearanceApi();
        $response = $api->single(
            binarySecurityToken: $csid->certificate,
            secret: $csid->secret,
            invoiceHash: $invoiceHash,
            uuid: $invoiceUuid,
            invoiceXml: $invoiceXml,
        );

        return new SubmissionResponse(
            validationResults: new ValidationResults(
                infoMessages: $response['validationResults']['infoMessages'],
                warningMessages: $response['validationResults']['warningMessages'],
                errorMessages: $response['validationResults']['errorMessages'],
                status: $response['validationResults']['status'],
            ),
            status: $isSimplified ? $response['reportingStatus'] : $response['clearanceStatus'],
        );
    }
}
