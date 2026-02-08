<?php

declare(strict_types=1);

namespace Zid\Zatca;

use Zid\Zatca\API\ZatcaClient;
use Zid\Zatca\Entities\CSID;
use Zid\Zatca\Entities\SubmissionResponse;
use Zid\Zatca\Entities\ValidationResults;
use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Exceptions\ZatcaApiException;

class InvoiceSubmissionService
{
    private ZatcaClient $zatcaClient;

    public function __construct(
        ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
        ?ZatcaClient $client = null
    ) {
        $this->zatcaClient = $client ?? new ZatcaClient($environment);
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
        try {
            $response = $api->single(
                binarySecurityToken: $csid->certificate,
                secret: $csid->secret,
                invoiceHash: $invoiceHash,
                uuid: $invoiceUuid,
                invoiceXml: $invoiceXml,
            );
        } catch (ZatcaApiException $e) {
            $previousException = $e->getPrevious();
            if (!$previousException instanceof \GuzzleHttp\Exception\ClientException) {
                throw $e;
            }
            $responseBody = $previousException->getResponse()->getBody();
            $responseBody->rewind();
            $response = json_decode($responseBody->getContents(), true);
            if (!isset($response['validationResults'])) {
                throw $e;
            }
        }

        $submissionStatus = $isSimplified ? $response['reportingStatus'] : $response['clearanceStatus'];
        $isSubmitted = $submissionStatus === ($isSimplified ? 'REPORTED' : 'CLEARED');
        return new SubmissionResponse(
            validationResults: new ValidationResults(
                infoMessages: $response['validationResults']['infoMessages'],
                warningMessages: $response['validationResults']['warningMessages'],
                errorMessages: $response['validationResults']['errorMessages'],
                status: $response['validationResults']['status'],
            ),
            status: $submissionStatus,
            isSubmitted: $isSubmitted,
        );
    }
}
