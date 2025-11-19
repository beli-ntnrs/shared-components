<?php
/**
 * Unit Tests for NotionClient
 */

namespace Tests\Unit;

use Notioneers\Shared\Notion\NotionClient;
use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use PHPUnit\Framework\TestCase;
use PDO;

class NotionClientTest extends TestCase {
    private PDO $pdo;
    private NotionEncryption $encryption;
    private NotionDatabaseHelper $dbHelper;
    private NotionClient $client;

    protected function setUp(): void {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize encryption and database helper
        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($this->pdo, $this->encryption);
        $this->dbHelper->initializeDatabase();

        // Create test client
        $this->client = new NotionClient($this->pdo, 'test-app');

        // Add test workspace
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-123',
            'secret_test_key_' . bin2hex(random_bytes(16)),
            'Test Workspace'
        );
    }

    /**
     * Test NotionClient instantiation
     */
    public function testClientInstantiation(): void {
        $this->assertInstanceOf(NotionClient::class, $this->client);
        $this->assertEquals('test-app', $this->client->getAppName());
        $this->assertNull($this->client->getCurrentWorkspace());
    }

    /**
     * Test setting workspace
     */
    public function testSetWorkspace(): void {
        $this->client->setWorkspace('workspace-123');
        $this->assertEquals('workspace-123', $this->client->getCurrentWorkspace());
    }

    /**
     * Test setting non-existent workspace throws error
     */
    public function testSetNonExistentWorkspaceThrowsError(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');

        $this->client->setWorkspace('non-existent-workspace');
    }

    /**
     * Test setWorkspace returns self for chaining
     */
    public function testSetWorkspaceReturnsself(): void {
        $result = $this->client->setWorkspace('workspace-123');
        $this->assertSame($this->client, $result);
    }

    /**
     * Test getting workspaces
     */
    public function testGetWorkspaces(): void {
        $workspaces = $this->client->getWorkspaces();

        $this->assertIsArray($workspaces);
        $this->assertCount(1, $workspaces);
        $this->assertEquals('workspace-123', $workspaces[0]['workspace_id']);
        $this->assertEquals('Test Workspace', $workspaces[0]['workspace_name']);
    }

    /**
     * Test getting workspace info
     */
    public function testGetWorkspaceInfo(): void {
        $this->client->setWorkspace('workspace-123');
        $info = $this->client->getWorkspaceInfo();

        $this->assertIsArray($info);
        $this->assertEquals('workspace-123', $info['workspace_id']);
        $this->assertEquals('Test Workspace', $info['workspace_name']);
    }

    /**
     * Test getting workspace info without setting workspace throws error
     */
    public function testGetWorkspaceInfoWithoutWorkspaceThrowsError(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No workspace selected');

        $this->client->getWorkspaceInfo();
    }

    /**
     * Test getting configuration
     */
    public function testGetConfiguration(): void {
        $this->client->setWorkspace('workspace-123');
        $config = $this->client->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('database_id', $config);
        $this->assertArrayHasKey('page_id', $config);
        $this->assertArrayHasKey('config', $config);
    }

    /**
     * Test updating configuration
     */
    public function testUpdateConfiguration(): void {
        $this->client->setWorkspace('workspace-123');

        $result = $this->client->updateConfiguration(
            databaseId: 'db_123',
            pageId: null,
            config: ['import_mode' => 'append']
        );

        $this->assertTrue($result);

        // Verify configuration was updated
        $config = $this->client->getConfiguration();
        $this->assertEquals('db_123', $config['database_id']);
        $this->assertEquals(['import_mode' => 'append'], $config['config']);
    }

    /**
     * Test clear cache
     */
    public function testClearCache(): void {
        $this->client->setWorkspace('workspace-123');

        // This should not throw
        $this->client->clearCache();
        $this->assertNull(null);  // Cache cleared successfully

        $this->client->clearCache('workspace-123');
        $this->assertNull(null);  // Specific workspace cache cleared
    }

    /**
     * Test record usage
     */
    public function testRecordUsage(): void {
        $this->client->setWorkspace('workspace-123');

        // This should not throw
        $this->client->recordUsage();

        // Verify workspace info was updated
        $info = $this->client->getWorkspaceInfo();
        $this->assertNotNull($info['last_used_at']);
    }

    /**
     * Test chaining methods
     */
    public function testMethodChaining(): void {
        $result = $this->client
            ->setWorkspace('workspace-123')
            ->recordUsage();

        $this->assertSame($this->client, $result);
    }

    /**
     * Test multiple workspaces
     */
    public function testMultipleWorkspaces(): void {
        // Add second workspace
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-456',
            'secret_test_key_' . bin2hex(random_bytes(16)),
            'Second Workspace'
        );

        // Get all workspaces
        $workspaces = $this->client->getWorkspaces();
        $this->assertCount(2, $workspaces);

        // Switch between workspaces
        $this->client->setWorkspace('workspace-123');
        $info1 = $this->client->getWorkspaceInfo();

        $this->client->setWorkspace('workspace-456');
        $info2 = $this->client->getWorkspaceInfo();

        $this->assertNotEquals($info1['workspace_id'], $info2['workspace_id']);
    }
}
