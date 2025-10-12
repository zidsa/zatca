<?php

namespace Zid\Zatca\API;

class ReportingApi implements ZatcaApiInterface
{
    public function __construct(protected ZatcaClient $client)
    {
    }

    public function single(string $binarySecurityToken, string $secret, string $invoiceHash, string $uuid, string $invoiceXml, string $language = 'en'): array
    {
        return $this->client->throwsExceptionOnError(false)->postRequest('invoices/reporting/single', [
            "invoiceHash" => $invoiceHash,
            "uuid" => $uuid,
            "invoice" => base64_encode($invoiceXml),
        ], [
            'Authorization' => 'Basic ' . base64_encode(
                "$binarySecurityToken:$secret"
            ),
            'Accept-Language' => $language,
            'Content-Type' => 'application/json',
            'Clearance-Status' => '1',
        ]);
    }
}
