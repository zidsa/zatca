<?php

declare(strict_types=1);

namespace Zid\Zatca\API;

class ClearanceApi implements ZatcaApiInterface
{
    public function __construct(protected ZatcaClient $client)
    {
    }

    public function single(string $binarySecurityToken, string $secret, string $invoiceHash, string $uuid, string $invoiceXml, string $language = 'en'): array
    {
        return $this->client->postRequest('invoices/clearance/single', [
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
