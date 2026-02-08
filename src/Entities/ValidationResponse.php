<?php

declare(strict_types=1);

namespace Zid\Zatca\Entities;

class ValidationResponse
{
    public function __construct(
        public ValidationResults $validationResults,
        public ?string $reportingStatus,
        public ?string $clearanceStatus,
        public ?string $qrSellerStatus,
        public ?string $qrBuyerStatus,
    ) {
    }
}
