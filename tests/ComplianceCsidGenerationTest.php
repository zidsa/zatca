<?php

namespace Zid\Zatca\Tests;

use PHPUnit\Framework\TestCase;
use Zid\Zatca\ComplianceService;
use Zid\Zatca\Enums\ZatcaEnvironment;

/**
 * Test class for the Certificate helper.
 */
class ComplianceCsidGenerationTest extends TestCase
{
    public function testQrGeneration()
    {
        $complianceService = new ComplianceService(ZatcaEnvironment::SANDBOX);

        $csrCertificatePath = __DIR__ . '/fixtures/certificate.csr';
        $csr = file_get_contents($csrCertificatePath);
        $ccsid = $complianceService->requestComplianceCertificate(
            b64Csr: base64_encode($csr),
            otp: '123456'
        );

        $this->assertEquals(
            '1234567890123',
            $ccsid->requestId,
            'Request ID is not valid'
        );

    }
}
