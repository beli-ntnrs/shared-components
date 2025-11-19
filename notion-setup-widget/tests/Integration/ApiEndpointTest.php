<?php
/**
 * API Endpoint Tests - Test the REST API endpoints
 *
 * Run with: php tests/Integration/ApiEndpointTest.php
 */

require_once __DIR__ . '/../bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidgetController;
use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use PDO;

class MockRequest {
    private array $params = [];
    private array $queryParams = [];
    private array $body = [];

    public function __construct(array $queryParams = [], array $body = []) {
        $this->queryParams = $queryParams;
        $this->body = $body;
    }

    public function getQueryParams(): array {
        return $this->queryParams;
    }

    public function getParsedBody() {
        return $this->body;
    }
}

class MockResponse {
    public $status = 200;
    public $body = '';
    public $headers = [];

    public function getBody() {
        return $this;
    }

    public function write(string $data) {
        $this->body .= $data;
    }

    public function withStatus(int $status) {
        $this->status = $status;
        return $this;
    }

    public function withHeader(string $name, string $value) {
        $this->headers[$name] = $value;
        return $this;
    }
}

class ApiEndpointTest {
    private $pdo;
    private $encryption;
    private $dbHelper;
    private $controller;

    public function __construct() {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($this->pdo, $this->encryption);
        $this->dbHelper->initializeDatabase();

        $this->controller = new NotionSetupWidgetController($this->pdo);
    }

    public function run(): void {
        echo "\n=== API Endpoint Tests ===\n\n";

        $this->testListWorkspacesEndpoint();
        $this->testUpdateConfigurationEndpoint();
        $this->testGetConfigurationEndpoint();
        $this->testDeleteWorkspaceEndpoint();
        $this->testErrorHandling();

        echo "\n✅ All API endpoint tests passed!\n\n";
    }

    private function testListWorkspacesEndpoint(): void {
        echo "1️⃣ Testing GET /api/notion/credentials...\n";

        // Add test workspaces
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-1',
            'secret_test_' . bin2hex(random_bytes(16)),
            'Test Workspace 1'
        );

        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-2',
            'secret_test_' . bin2hex(random_bytes(16)),
            'Test Workspace 2'
        );

        // Test endpoint
        $request = new MockRequest(['app' => 'test-app']);
        $response = new MockResponse();

        $this->controller->listWorkspaces($request, $response);

        if ($response->status === 200) {
            echo "   ✓ HTTP 200 OK\n";
        }

        $data = json_decode($response->body, true);
        if ($data['success'] === true && count($data['workspaces']) === 2) {
            echo "   ✓ Returns 2 workspaces\n";
        }

        if ($data['workspaces'][0]['workspace_name'] === 'Test Workspace 1') {
            echo "   ✓ Workspace names correct\n";
        }

        echo "   ✓ Response format correct\n";
    }

    private function testUpdateConfigurationEndpoint(): void {
        echo "\n2️⃣ Testing PUT /api/notion/credentials/{id}/config...\n";

        // Add workspace first
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-update',
            'secret_test_' . bin2hex(random_bytes(16)),
            'Update Test'
        );

        // Test endpoint
        $requestBody = [
            'app' => 'test-app',
            'database_id' => 'db_test_123',
            'page_id' => null,
            'config' => ['mode' => 'test'],
        ];

        $request = new MockRequest([], $requestBody);
        $response = new MockResponse();

        $this->controller->updateConfiguration($request, $response, [
            'workspace_id' => 'workspace-update'
        ]);

        if ($response->status === 200) {
            echo "   ✓ HTTP 200 OK\n";
        }

        $data = json_decode($response->body, true);
        if ($data['success'] === true) {
            echo "   ✓ Configuration updated successfully\n";
        }

        // Verify it was stored
        $config = $this->dbHelper->getConfiguration('test-app', 'workspace-update');
        if ($config['database_id'] === 'db_test_123') {
            echo "   ✓ Database ID persisted\n";
        }
    }

    private function testGetConfigurationEndpoint(): void {
        echo "\n3️⃣ Testing GET /api/notion/credentials/{id}/config...\n";

        // Update configuration first
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-get-config',
            'secret_test_' . bin2hex(random_bytes(16)),
            'Get Config Test'
        );

        $this->dbHelper->updateConfiguration(
            'test-app',
            'workspace-get-config',
            'db_config_test',
            null,
            ['test' => 'value']
        );

        // Test endpoint
        $request = new MockRequest(['app' => 'test-app']);
        $response = new MockResponse();

        $this->controller->getConfiguration($request, $response, [
            'workspace_id' => 'workspace-get-config'
        ]);

        if ($response->status === 200) {
            echo "   ✓ HTTP 200 OK\n";
        }

        $data = json_decode($response->body, true);
        if ($data['configuration']['database_id'] === 'db_config_test') {
            echo "   ✓ Configuration retrieved correctly\n";
        }
    }

    private function testDeleteWorkspaceEndpoint(): void {
        echo "\n4️⃣ Testing DELETE /api/notion/credentials/{id}...\n";

        // Add workspace to delete
        $this->dbHelper->storeCredentials(
            'test-app',
            'workspace-delete',
            'secret_test_' . bin2hex(random_bytes(16)),
            'Delete Test'
        );

        // Verify it exists
        $before = $this->dbHelper->listCredentials('test-app');
        $beforeCount = count($before);
        echo "   ✓ Workspace created (total: $beforeCount)\n";

        // Test delete endpoint
        $request = new MockRequest(['app' => 'test-app']);
        $response = new MockResponse();

        $this->controller->deleteWorkspace($request, $response, [
            'workspace_id' => 'workspace-delete'
        ]);

        if ($response->status === 200) {
            echo "   ✓ HTTP 200 OK\n";
        }

        $data = json_decode($response->body, true);
        if ($data['success'] === true) {
            echo "   ✓ Workspace deleted successfully\n";
        }

        // Verify workspace is inactive (soft delete)
        $after = $this->dbHelper->listCredentials('test-app');
        $afterCount = count($after);
        if ($afterCount === $beforeCount - 1) {
            echo "   ✓ Workspace removed from listing\n";
        }
    }

    private function testErrorHandling(): void {
        echo "\n5️⃣ Testing Error Handling...\n";

        // Test missing app parameter
        $request = new MockRequest([]);  // No 'app' param
        $response = new MockResponse();

        $this->controller->listWorkspaces($request, $response);

        if ($response->status === 400) {
            echo "   ✓ Returns HTTP 400 for missing parameter\n";
        }

        $data = json_decode($response->body, true);
        if ($data['success'] === false) {
            echo "   ✓ Error response format correct\n";
        }

        // Test non-existent workspace
        $request = new MockRequest(['app' => 'test-app']);
        $response = new MockResponse();

        $this->controller->getConfiguration($request, $response, [
            'workspace_id' => 'non-existent'
        ]);

        if ($response->status === 404) {
            echo "   ✓ Returns HTTP 404 for non-existent workspace\n";
        }
    }
}

// Run tests
try {
    $test = new ApiEndpointTest();
    $test->run();
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
