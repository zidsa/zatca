<?php

namespace Zid\Zatca\API;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Zid\Zatca\Enums\ZatcaEnvironment;
use Zid\Zatca\Exceptions\ZatcaApiException;

class ZatcaClient implements ZatcaClientInterface
{
    private const SUCCESS_STATUS_CODES = [200, 202];
    private ClientInterface $httpClient;
    private array $headers = [
        'Accept-Version' => 'V2',
        'Accept' => 'application/json',
    ];

    public function __construct(
        private ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
        ?ClientInterface $client = null
    ) {
        $this->httpClient  = $client ?? new Client([
            'base_uri' => $this->getBaseUri(),
            'timeout'  => 30,
            'verify'   => true,
        ]);
    }

    protected function getBaseUri(): string
    {
        return match ($this->environment) {
            ZatcaEnvironment::PRODUCTION => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core/',
            ZatcaEnvironment::SIMULATION => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation/',
            ZatcaEnvironment::SANDBOX    => 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/',
        };
    }

    private function send(
        string $method,
        string $endpoint,
        array $payload = [],
        array $headers = [],
    ): array {
        $options = [
            'headers' => array_merge($this->headers, $headers),
            'json' => $payload,
        ];

        $response = $this->httpClient->request($method, $endpoint, $options);
        $statusCode = $response->getStatusCode();

        if (!in_array($statusCode, self::SUCCESS_STATUS_CODES, true)) {
            throw new ZatcaApiException("Request failed with status code $statusCode.");
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function postRequest(
        string $endpoint,
        array $payload = [],
        array $headers = [],
    ): array {
        return $this->send('POST', $endpoint, $payload, $headers);
    }

    public function complianceApi(): ComplianceApi
    {
        return new ComplianceApi($this);
    }

    public function productionApi(): ProductionApi
    {
        return new ProductionApi($this);
    }
}
