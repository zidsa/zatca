<?php

namespace Zid\Zatca\Entities;

class InvoiceSigningResult
{
    public function __construct(
        public string $invoiceHash,
        public string $b64SignedInvoice,
        public string $uuid,
    ) {
    }
}
