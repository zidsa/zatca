<?php

namespace Zid\Zatca;

use Exception;

class GetPublicKeyAndSignatureService
{
    public function get(string $certificateBase64): array
    {
        try {
            // Step 1: Create a temporary file for the certificate
            $tempFile = tempnam(sys_get_temp_dir(), 'cert');

            // Step 2: Write the certificate content to the temporary file
            $certContent = "-----BEGIN CERTIFICATE-----\n";
            $certContent .= chunk_split($certificateBase64, 64, "\n");
            $certContent .= "-----END CERTIFICATE-----\n";

            if (file_put_contents($tempFile, $certContent) === false) {
                throw new Exception("Cannot write certificate to temporary file");
            }

            // Step 3: Read the certificate
            $cert = openssl_x509_read($certContent);

            // Step 4: Extract the public key
            $pubKey = openssl_pkey_get_public($cert);
            $pubKeyDetails = openssl_pkey_get_details($pubKey);

            // Step 5: Construct raw public key from x and y components
            $x = $pubKeyDetails['ec']['x'];
            $y = $pubKeyDetails['ec']['y'];

            // Ensure x and y are 32 bytes long for secp256k1
            $x = str_pad($x, 32, "\0", STR_PAD_LEFT);
            $y = str_pad($y, 32, "\0", STR_PAD_LEFT);

            // Prepare the raw public key in uncompressed DER format
            $publicKeyDER = pack('C*',
                0x30, // SEQUENCE
                0x56, // Total length of the sequence (to be calculated)
                0x30, // SEQUENCE for the algorithm
                0x10, // Length of the OID
                0x06, 0x07, 0x2A, 0x86, 0x48, 0xCE, 0x3D, 0x02, 0x01, // OID for EC
                0x06, 0x05, 0x2B, 0x81, 0x04, 0x00, 0x0A, // OID for secp256k1
                0x03, 0x42, // BIT STRING tag and length
                0x00, 0x04, // Length of the uncompressed public key (2 * 32 bytes)
                ...array_values(unpack('C*', $x)), // x
                ...array_values(unpack('C*', $y))  // y
            );

            // Step 6: Extract the ECDSA signature from DER data
            $certPEM = file_get_contents($tempFile);
            if (!preg_match('/-+BEGIN CERTIFICATE-+\s+(.+)\s+-+END CERTIFICATE-+/s', $certPEM, $matches)) {
                throw new Exception("Error extracting DER data from certificate.");
            }

            $derData = base64_decode($matches[1]);
            $sequencePos = strpos($derData, "\x30", -72);
            $signature = substr($derData, $sequencePos);

            // Return the correctly extracted details
            return [
                'public_key' => base64_encode($publicKeyDER),  // PEM format for public key
                'public_key_raw' => $publicKeyDER, // Raw public key in DER format
                'signature' => $signature         // Raw ECDSA signature bytes
            ];

        } catch (Exception $e) {
            throw new Exception("[Error] Failed to process certificate: " . $e->getMessage());
        } finally {
            // Clean up resources
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
