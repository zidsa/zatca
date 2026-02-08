<?php

declare(strict_types=1);

namespace Zid\Zatca;

use Exception;

class GetDigitalSignatureService
{
    public function get(string $invoiceHash, string $privateKeyContent): string
    {
        $hashBytes = base64_decode($invoiceHash);

        if ($hashBytes === false) {
            throw new Exception("Failed to decode the base64-encoded XML hashing.");
        }

        $privateKeyContent = str_replace(["\n", "\t"], '', $privateKeyContent);

        if (strpos($privateKeyContent, "-----BEGIN EC PRIVATE KEY-----") === false &&
            strpos($privateKeyContent, "-----END EC PRIVATE KEY-----") === false) {
            $privateKeyContent = "-----BEGIN EC PRIVATE KEY-----\n" .
                chunk_split($privateKeyContent, 64, "\n") .
                "-----END EC PRIVATE KEY-----\n";
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if ($privateKey === false) {
            throw new Exception("Failed to read private key.");
        }

        $signature = '';

        if (!openssl_sign($hashBytes, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Failed to sign the data.");
        }

        return base64_encode($signature);
    }
}
