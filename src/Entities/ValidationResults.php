<?php

namespace Zid\Zatca\Entities;

class ValidationResults
{
    public function __construct(
        public array $infoMessages,
        public array $warningMessages,
        public array $errorMessages,
        public string $status,
    ) {
    }
}
