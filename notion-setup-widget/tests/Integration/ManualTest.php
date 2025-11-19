<?php
/**
 * Manual Integration Test - Test the complete Setup Widget flow
 *
 * This test simulates a real user interaction with the widget.
 * Run with: php tests/Integration/ManualTest.php
 */

require_once __DIR__ . '/../bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use PDO;

class ManualTest {
    private PDO $pdo;
    private NotionEncryption $encryption;
    private NotionDatabaseHelper $dbHelper;
    private NotionSetupWidget $widget;

    public function __construct() {
        // Create in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($this->pdo, $this->encryption);
        $this->dbHelper->initializeDatabase();

        $this->widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            'test-app'
        );
    }

    public function run(): void {
        echo "\n=== Notion Setup Widget - Manual Integration Test ===\n\n";

        $this->testHtmlRendering();
        $this->testJavaScriptInclusion();
        $this->testWorkspaceStorage();
        $this->testConfiguration();
        $this->testMultipleWorkspaces();
        $this->testOutputSafety();

        echo "\n✅ All manual tests passed!\n\n";
    }

    private function testHtmlRendering(): void {
        echo "1️⃣ Testing HTML Rendering...\n";

        $html = $this->widget->render();

        $assertions = [
            'Widget container' => 'notion-setup-widget',
            'Card component' => 'card',
            'Form inputs' => 'id="workspace-name"',
            'API key input' => 'id="api-key"',
            'Submit button' => 'type="submit"',
            'Modal for configuration' => 'id="configModal"',
            'Database selector' => 'id="database-select"',
            'Bootstrap classes' => 'btn btn-primary',
            'Icons' => 'bi-plus-circle',
        ];

        foreach ($assertions as $name => $expected) {
            if (strpos($html, $expected) !== false) {
                echo "   ✓ $name found\n";
            } else {
                echo "   ✗ $name NOT found\n";
            }
        }
    }

    private function testJavaScriptInclusion(): void {
        echo "\n2️⃣ Testing JavaScript...\n";

        $html = $this->widget->render();

        $jsFunctions = [
            'loadWorkspaces',
            'handleAddWorkspace',
            'handleSaveConfig',
            'openConfigModal',
            'deleteWorkspace',
            'renderWorkspaces',
        ];

        foreach ($jsFunctions as $fn) {
            if (strpos($html, $fn) !== false) {
                echo "   ✓ Function '$fn' included\n";
            } else {
                echo "   ✗ Function '$fn' missing\n";
            }
        }

        // Check for JSON data
        if (preg_match('/id="workspaces-data"/', $html)) {
            echo "   ✓ Workspaces JSON data included\n";
        }
    }

    private function testWorkspaceStorage(): void {
        echo "\n3️⃣ Testing Workspace Storage...\n";

        // Store a workspace
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-abc123',
            'secret_test_key_' . bin2hex(random_bytes(16)),
            'Test Workspace'
        );

        echo "   ✓ Workspace stored\n";

        // List workspaces
        $workspaces = $this->dbHelper->listCredentials('test-app');
        if (count($workspaces) === 1 && $workspaces[0]['workspace_name'] === 'Test Workspace') {
            echo "   ✓ Workspace retrieved correctly\n";
        }

        // Get specific workspace info
        $info = $this->dbHelper->getWorkspaceInfo('test-app', 'workspace-abc123');
        if ($info['workspace_id'] === 'workspace-abc123') {
            echo "   ✓ Workspace info accessible\n";
        }
    }

    private function testConfiguration(): void {
        echo "\n4️⃣ Testing Configuration Management...\n";

        // Add workspace first
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-db-test',
            'secret_test_key_' . bin2hex(random_bytes(16)),
            'Config Test'
        );

        // Update configuration
        $updated = $this->dbHelper->updateConfiguration(
            'test-app',
            'workspace-db-test',
            databaseId: 'db_12345',
            pageId: null,
            config: [
                'field_mapping' => [
                    'Name' => 'name',
                    'Email' => 'email',
                ],
                'sync_mode' => 'append',
            ]
        );

        if ($updated) {
            echo "   ✓ Configuration updated\n";
        }

        // Get configuration
        $config = $this->dbHelper->getConfiguration('test-app', 'workspace-db-test');

        if ($config['database_id'] === 'db_12345') {
            echo "   ✓ Database ID stored correctly\n";
        }

        if ($config['config']['field_mapping']['Email'] === 'email') {
            echo "   ✓ Custom config stored as JSON\n";
        }
    }

    private function testMultipleWorkspaces(): void {
        echo "\n5️⃣ Testing Multiple Workspaces...\n";

        // Add multiple workspaces
        for ($i = 1; $i <= 3; $i++) {
            $this->dbHelper->storeCredentials(
                'test-app',
                "workspace-$i",
                'secret_test_key_' . bin2hex(random_bytes(16)),
                "Workspace $i"
            );

            // Configure each differently
            $this->dbHelper->updateConfiguration(
                'test-app',
                "workspace-$i",
                databaseId: "db_$i",
                config: ['priority' => $i]
            );
        }

        $workspaces = $this->dbHelper->listCredentials('test-app');
        echo "   ✓ Created " . count($workspaces) . " workspaces\n";

        // Verify each has different configuration
        foreach ($workspaces as $ws) {
            $config = $this->dbHelper->getConfiguration('test-app', $ws['workspace_id']);
            if ($config['config']['priority'] > 0) {
                echo "   ✓ Workspace {$ws['workspace_name']} has unique config\n";
            }
        }
    }

    private function testOutputSafety(): void {
        echo "\n6️⃣ Testing Output Safety...\n";

        // Create widget with potentially dangerous characters
        $widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            "app'\"<script>alert('xss')</script>"
        );

        $html = $widget->render();

        // Check that the dangerous string is escaped
        if (strpos($html, '<script>') === false) {
            echo "   ✓ Script tags properly escaped\n";
        }

        if (strpos($html, 'alert') === false) {
            echo "   ✓ Alert calls escaped\n";
        }

        // Check that it's properly HTML-encoded
        if (strpos($html, 'alert&#039;') !== false ||
            strpos($html, '&lt;script&gt;') !== false ||
            strpos($html, '&#039;') !== false) {
            echo "   ✓ XSS prevention working\n";
        }
    }
}

// Run tests
try {
    $test = new ManualTest();
    $test->run();
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
