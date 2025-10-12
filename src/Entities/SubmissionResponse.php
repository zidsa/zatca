<?php

namespace Zid\Zatca\Entities;

class SubmissionResponse
{
    public function __construct(
        public ValidationResults $validationResults,
        public string $status, // reportingStatus / clearanceStatus
        public bool $isSubmitted,
    ) {
    }
}
