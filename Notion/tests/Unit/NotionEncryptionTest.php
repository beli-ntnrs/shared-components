<?php

namespace Tests\Unit\Notion;

use Notioneers\Shared\Notion\NotionEncryption;
use PHPUnit\Framework\TestCase;

class NotionEncryptionTest extends TestCase {
    private NotionEncryption $encryption;

    protected function setUp(): void {
        putenv('ENCRYPTION_MASTER_KEY=test_key_' . bin2hex(random_bytes(16)));
        $this->encryption = new NotionEncryption();
    }

    public function testEncryptDecryptRoundTrip(): void {
        $plaintext = 'secret_abc123xyz789';

        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);

        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    public function testDifferentEncryptionsProduceDifferentCiphertexts(): void {
        $plaintext = 'secret_same_value';

        $encrypted1 = $this->encryption->encrypt($plaintext);
        $encrypted2 = $this->encryption->encrypt($plaintext);

        // Different IVs mean different ciphertexts
        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both decrypt to same value
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted2));
    }

    public function testTamperingDetection(): void {
        $encrypted = $this->encryption->encrypt('secret_original');

        // Tamper with the encrypted data
        $tampered = base64_encode(base64_decode($encrypted) . 'X');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HMAC verification failed');

        $this->encryption->decrypt($tampered);
    }

    public function testInvalidBase64Handling(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid encrypted data format');

        $this->encryption->decrypt('not-valid-base64!!!');
    }

    public function testEncryptionKeyMissing(): void {
        putenv('ENCRYPTION_MASTER_KEY=');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ENCRYPTION_MASTER_KEY not set');

        new NotionEncryption();
    }

    public function testLongValueEncryption(): void {
        $longValue = 'secret_' . str_repeat('x', 10000);

        $encrypted = $this->encryption->encrypt($longValue);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($longValue, $decrypted);
    }

    public function testSpecialCharactersEncryption(): void {
        $special = 'secret_!@#$%^&*()_+-=[]{}|;:,.<>?';

        $encrypted = $this->encryption->encrypt($special);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($special, $decrypted);
    }

    public function testEmptyStringEncryption(): void {
        $encrypted = $this->encryption->encrypt('');
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals('', $decrypted);
    }
}
