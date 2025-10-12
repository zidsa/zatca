<?php

namespace Zid\Zatca\Entities;

class InvoiceHashingResult
{
    public function __construct(
        public string $invoiceHash,
        public string $uuid,
        public string $b64Invoice,
        public string $base64CanonicalXml,
    ) {
    }
}
