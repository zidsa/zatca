<?php

declare(strict_types=1);

namespace Zid\Zatca\Entities;

class CSID
{
    public function __construct(
        public string $certificate,
        public string $secret,
        public int $requestId,
    ) {
    }

    public static function loadFromJson(string $filepath): self
    {
        $data = json_decode(file_get_contents($filepath), true);

        if (!is_array($data) || !isset($data['certificate'], $data['secret'], $data['requestId'])) {
            throw new \InvalidArgumentException('Invalid JSON file.');
        }

        return new self(
            certificate: $data['certificate'],
            secret: $data['secret'],
            requestId: (int) $data['requestId'],
        );
    }

    public function saveAsJson(string $filepath): void
    {
        $data = [
            'certificate' => $this->certificate,
            'secret' => $this->secret,
            'requestId' => $this->requestId,
        ];

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($filepath, $jsonContent);
    }
}
