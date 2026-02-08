<?php

declare(strict_types=1);

namespace Zid\Zatca\Entities;

class InvoiceSigningResult
{
    public function __construct(
        public string $signature,
        public string $b64SignedInvoice,
        public string $b64QrCode,
    ) {
    }
}
