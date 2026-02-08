<?php

declare(strict_types=1);

namespace Zid\Zatca\API;

use GuzzleHttp\ClientInterface;
use Zid\Zatca\Enums\ZatcaEnvironment;

interface ZatcaClientInterface
{
    public function __construct(
        ZatcaEnvironment $environment = ZatcaEnvironment::SANDBOX,
        ?ClientInterface $client = null
    );

    public function postRequest(
        string $endpoint,
        array $payload = [],
        array $headers = [],
    ): array;

    public function complianceApi(): ZatcaApiInterface;

    public function productionApi(): ZatcaApiInterface;

    public function reportingApi(): ReportingApi;

    public function clearanceApi(): ClearanceApi;
}
