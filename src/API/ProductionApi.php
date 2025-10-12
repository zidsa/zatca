<?php

namespace Zid\Zatca\API;

class ProductionApi implements ZatcaApiInterface
{
    public function __construct(protected ZatcaClient $client)
    {
    }

    public function requestProductionCertificate(string $binarySecurityToken, string $secret, string $ccsidRequestId, string $language = 'en'): array
    {
        return $this->client->postRequest('production/csids', [
            'compliance_request_id' => $ccsidRequestId,
        ], [
            'Authorization' => 'Basic ' . base64_encode(
                "$binarySecurityToken:$secret"
            ),
            'Accept-Language' => $language,
            'Content-Type' => 'application/json',
        ]);
    }
}
