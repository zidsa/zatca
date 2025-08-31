<?php

namespace Zid\Zatca\API;

class ComplianceApi implements ZatcaApiInterface
{
    public function __construct(protected ZatcaClient $client)
    {
    }

    public function requestComplianceCertificate(string $csr, string $otp): array
    {
        return $this->client->postRequest('compliance', [
            'csr' => base64_encode($csr),
        ], [
            'OTP' => $otp,
        ]);
    }

    public function checkCompliance(string $binarySecurityToken, string $secret, string $invoiceHash, string $uuid, string $invoice, string $language = 'en'): array
    {
        return $this->client->postRequest('compliance/invoices', [
            'invoiceHash' => $invoiceHash,
            'uuid' => $uuid,
            'invoice' => $invoice,
        ], [
            'Authorization' => 'Basic ' . base64_encode(
                "$binarySecurityToken:$secret"
            ),
            'Accept-Language' => $language,



//            'accept: application/json',
//            'accept-language: en',
//            'Accept-Version: V2',
            'Content-Type: application/json',
        ]);
    }
}
