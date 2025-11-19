<?php

namespace Tests\Integration\Notion;

use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;
use PDO;
use PHPUnit\Framework\TestCase;

class NotionDatabaseHelperTest extends TestCase {
    private PDO $pdo;
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;

    protected function setUp(): void {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        putenv('ENCRYPTION_MASTER_KEY=test_key_' . bin2hex(random_bytes(16)));
        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($this->pdo, $this->encryption);

        // Initialize database
        $this->dbHelper->initializeDatabase();
    }

    public function testInitializeDatabase(): void {
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND name='notion_credentials'";
        $stmt = $this->pdo->query($query);
        $result = $stmt->fetch();

        $this->assertNotEmpty($result);
    }

    public function testStoreCredentials(): void {
        $id = $this->dbHelper->storeCredentials(
            'admintool',
            'workspace_123',
            'secret_abc123xyz',
            'My Workspace'
        );

        $this->assertGreaterThan(0, $id);

        // Verify stored encrypted
        $query = "SELECT api_key_encrypted FROM notion_credentials WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        $this->assertNotEmpty($row);
        $this->assertNotEquals('secret_abc123xyz', $row['api_key_encrypted']);
    }

    public function testGetCredentials(): void {
        $this->dbHelper->storeCredentials(
            'admintool',
            'workspace_123',
            'secret_abc123xyz',
            'My Workspace'
        );

        $creds = $this->dbHelper->getCredentials('admintool', 'workspace_123');

        $this->assertEquals('secret_abc123xyz', $creds['api_key']);
        $this->assertEquals('My Workspace', $creds['workspace_name']);
    }

    public function testGetCredentialsNotFound(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active credentials found');

        $this->dbHelper->getCredentials('admintool', 'nonexistent');
    }

    public function testListCredentials(): void {
        $this->dbHelper->storeCredentials('admintool', 'workspace_1', 'secret_123', 'Workspace 1');
        $this->dbHelper->storeCredentials('admintool', 'workspace_2', 'secret_456', 'Workspace 2');

        $list = $this->dbHelper->listCredentials('admintool');

        $this->assertCount(2, $list);
        $this->assertEquals('workspace_1', $list[0]['workspace_id']);
        $this->assertEquals('workspace_2', $list[1]['workspace_id']);
    }

    public function testListCredentialsEmpty(): void {
        $list = $this->dbHelper->listCredentials('nonexistent_app');

        $this->assertEmpty($list);
    }

    public function testDisableCredentials(): void {
        $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_abc', 'Test');

        $disabled = $this->dbHelper->disableCredentials('admintool', 'workspace_123');

        $this->assertTrue($disabled);

        // Should not be retrievable when disabled
        $this->expectException(\RuntimeException::class);
        $this->dbHelper->getCredentials('admintool', 'workspace_123');
    }

    public function testDeleteCredentials(): void {
        $id = $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_abc', 'Test');

        $deleted = $this->dbHelper->deleteCredentials('admintool', 'workspace_123');

        $this->assertTrue($deleted);

        // Verify deleted
        $query = "SELECT COUNT(*) as count FROM notion_credentials WHERE id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        $this->assertEquals(0, $row['count']);
    }

    public function testUpdateCredentialsOnDuplicate(): void {
        $id1 = $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_first', 'Test');

        // Store again with same app+workspace
        $id2 = $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_second', 'Test');

        // Should update, not create new
        $this->assertEquals($id1, $id2);

        $creds = $this->dbHelper->getCredentials('admintool', 'workspace_123');
        $this->assertEquals('secret_second', $creds['api_key']);
    }

    public function testValidation(): void {
        // Invalid app name
        $this->expectException(\InvalidArgumentException::class);
        $this->dbHelper->storeCredentials('', 'workspace_123', 'secret_abc', 'Test');
    }

    public function testValidationApiKeyFormat(): void {
        // API key must start with secret_
        $this->expectException(\InvalidArgumentException::class);
        $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'invalid_key', 'Test');
    }

    public function testRecordCredentialUsage(): void {
        $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_abc', 'Test');

        $this->dbHelper->recordCredentialUsage('admintool', 'workspace_123');

        $list = $this->dbHelper->listCredentials('admintool');
        $this->assertNotNull($list[0]['last_used_at']);
    }

    public function testMultipleAppsIndependent(): void {
        $this->dbHelper->storeCredentials('app1', 'workspace_123', 'secret_app1', 'Workspace');
        $this->dbHelper->storeCredentials('app2', 'workspace_123', 'secret_app2', 'Workspace');

        $creds1 = $this->dbHelper->getCredentials('app1', 'workspace_123');
        $creds2 = $this->dbHelper->getCredentials('app2', 'workspace_123');

        $this->assertEquals('secret_app1', $creds1['api_key']);
        $this->assertEquals('secret_app2', $creds2['api_key']);
    }

    public function testInactiveCredentialsNotRetrieved(): void {
        $this->dbHelper->storeCredentials('admintool', 'workspace_123', 'secret_abc', 'Test');
        $this->dbHelper->disableCredentials('admintool', 'workspace_123');

        $this->expectException(\RuntimeException::class);
        $this->dbHelper->getCredentials('admintool', 'workspace_123');
    }
}
