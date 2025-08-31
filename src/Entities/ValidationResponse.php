<?php

namespace Zid\Zatca\Entities;

class ValidationResponse
{
    public function __construct(
        public ValidationResults $validationResults,
        public ?string $reportingStatus,
        public string $clearanceStatus,
        public ?string $qrSellertStatus,
        public ?string $qrBuyertStatus,
    ) {
    }
}
