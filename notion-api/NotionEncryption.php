<?php
/**
 * NotionEncryption - Secure encryption/decryption for Notion API credentials
 *
 * Encrypts sensitive API keys before storing in database
 * Uses OPENSSL_AES_256_CBC with HMAC authentication
 */

namespace Notioneers\Shared\Notion;

class NotionEncryption {
    private const ALGORITHM = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const TAG_LENGTH = 16;

    private string $encryptionKey;
    private string $hmacKey;

    /**
     * Initialize encryption with keys from environment
     *
     * @throws \RuntimeException If encryption keys not configured
     */
    public function __construct() {
        $masterKey = getenv('ENCRYPTION_MASTER_KEY');

        if (!$masterKey) {
            throw new \RuntimeException(
                'ENCRYPTION_MASTER_KEY not set in .env. ' .
                'Generate with: php -r "echo bin2hex(random_bytes(32));"'
            );
        }

        // Derive keys from master key
        $this->encryptionKey = hash(self::HASH_ALGO, $masterKey . 'encryption', true);
        $this->hmacKey = hash(self::HASH_ALGO, $masterKey . 'hmac', true);
    }

    /**
     * Encrypt a plain text value (API key)
     *
     * @param string $plaintext The API key to encrypt
     * @return string Base64 encoded ciphertext with IV and HMAC
     */
    public function encrypt(string $plaintext): string {
        // Generate random IV for each encryption
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ALGORITHM), $strong);

        if (!$strong) {
            throw new \RuntimeException('Failed to generate cryptographically strong IV');
        }

        // Encrypt
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::ALGORITHM,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Create HMAC for authentication (prevents tampering)
        $hmac = hash_hmac(self::HASH_ALGO, $iv . $ciphertext, $this->hmacKey, true);

        // Return: IV + CIPHERTEXT + HMAC (all base64 encoded)
        return base64_encode($iv . $ciphertext . $hmac);
    }

    /**
     * Decrypt an encrypted value
     *
     * @param string $encrypted Base64 encoded ciphertext with IV and HMAC
     * @return string The decrypted API key
     * @throws \RuntimeException If decryption fails or HMAC is invalid
     */
    public function decrypt(string $encrypted): string {
        // Decode from base64
        $data = base64_decode($encrypted, true);

        if ($data === false) {
            throw new \RuntimeException('Invalid encrypted data format');
        }

        $ivLength = openssl_cipher_iv_length(self::ALGORITHM);

        if (strlen($data) < $ivLength + self::TAG_LENGTH) {
            throw new \RuntimeException('Encrypted data too short');
        }

        // Extract parts
        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength, -self::TAG_LENGTH);
        $hmac = substr($data, -self::TAG_LENGTH);

        // Verify HMAC (prevents tampering)
        $expectedHmac = hash_hmac(self::HASH_ALGO, $iv . $ciphertext, $this->hmacKey, true);

        if (!hash_equals($hmac, $expectedHmac)) {
            throw new \RuntimeException('HMAC verification failed - data may be tampered');
        }

        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::ALGORITHM,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plaintext;
    }
}
