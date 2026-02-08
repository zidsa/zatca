<?php

declare(strict_types=1);

namespace Zid\Zatca\Entities;

class ValidationResults
{
    /**
     * @param array<int, array{type: string, code: string, category: string, message: string, status: string}> $infoMessages
     * @param array<int, array{type: string, code: string, category: string, message: string, status: string}> $warningMessages
     * @param array<int, array{type: string, code: string, category: string, message: string, status: string}> $errorMessages
     * @param string $status
     */
    public function __construct(
        public array $infoMessages,
        public array $warningMessages,
        public array $errorMessages,
        public string $status,
    ) {
    }
}
