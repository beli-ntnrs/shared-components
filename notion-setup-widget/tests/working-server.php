<?php
/**
 * Notion Setup Widget - Fully Working Test Server
 *
 * Run: php tests/working-server.php
 * Visit: http://localhost:8080
 */

// Setup
require_once __DIR__ . '/bootstrap.php';

// Use fixed encryption key for development (not random)
// This ensures tokens encrypted in one session can be decrypted in another
// IMPORTANT: Must be set AFTER bootstrap.php to override its random key
putenv('ENCRYPTION_MASTER_KEY=00112233445566778899aabbccddeeff00112233445566778899aabbccddeeff');

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionSetupWidgetController;
use Notioneers\Shared\Notion\NotionEncryption;
use PDO;

// Setup database
$dbPath = sys_get_temp_dir() . '/notion-widget-working.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$encryption = new NotionEncryption();
$dbHelper = new \Notioneers\Shared\Notion\NotionDatabaseHelper($pdo, $encryption);
$dbHelper->initializeDatabase();

// Parse request
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];
$request_body = file_get_contents('php://input');
$request_json = json_decode($request_body, true) ?? [];

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($request_method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Response helper
function json_response($data, $status = 200)
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ============ API Routes ============

$controller = new NotionSetupWidgetController($pdo);

// Helper to create request/response objects
function create_objects($body, $queryParams = [])
{
    $request = new class ($body, $queryParams) {
        private $body;
        private $queryParams;
        public function __construct($body, $queryParams)
        {
            $this->body = $body;
            $this->queryParams = $queryParams;
        }
        public function getParsedBody()
        {
            return $this->body;
        }
        public function getQueryParams()
        {
            return $this->queryParams;
        }
    };

    $response = new class {
        public function getBody()
        {
            return new class {
                public function write($s)
                {
                    echo $s;
                }
            };
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
    };

    return [$request, $response];
}

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
parse_str($queryString, $queryParams);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// POST /api/notion/validate-token
if ($request_method === 'POST' && $path === '/api/notion/validate-token') {
    [$req, $res] = create_objects($request_json, $queryParams);
    $controller->validateToken($req, $res);
    exit;
}

// POST /api/notion/credentials
if ($request_method === 'POST' && $path === '/api/notion/credentials') {
    [$req, $res] = create_objects($request_json, $queryParams);
    $controller->createWorkspace($req, $res);
    exit;
}

// GET /api/notion/credentials
if ($request_method === 'GET' && $path === '/api/notion/credentials') {
    [$req, $res] = create_objects([], $queryParams);
    $controller->listWorkspaces($req, $res);
    exit;
}

// DELETE /api/notion/credentials/{id}
if ($request_method === 'DELETE' && preg_match('#^/api/notion/credentials/([^/]+)$#', $path, $matches)) {
    [$req, $res] = create_objects([], $queryParams);
    $controller->deleteWorkspace($req, $res, ['workspace_id' => $matches[1]]);
    exit;
}

// PATCH /api/notion/credentials/{id}
if ($request_method === 'PATCH' && preg_match('#^/api/notion/credentials/([^/]+)$#', $path, $matches)) {
    [$req, $res] = create_objects($request_json, $queryParams);
    $controller->renameWorkspace($req, $res, ['workspace_id' => $matches[1]]);
    exit;
}

// ============ Legacy API Compatibility Routes (for tests/index.php) ============

// POST /api/validate-token
if ($request_method === 'POST' && $path === '/api/validate-token') {
    [$req, $res] = create_objects($request_json, $queryParams);
    $controller->validateToken($req, $res);
    exit;
}

// POST /api/save-token
if ($request_method === 'POST' && $path === '/api/save-token') {
    // Adapter: Transform legacy payload to new controller format
    $legacyBody = $request_json;
    $newBody = [
        'api_key' => $legacyBody['token'] ?? '',
        'workspace_name' => $legacyBody['token_name'] ?? '',
        'app' => $legacyBody['app'] ?? '',
        'workspace_id' => 'ws_' . substr(md5(uniqid()), 0, 10) // Generate ID if missing
    ];

    [$req, $res] = create_objects($newBody, $queryParams);
    $controller->createWorkspace($req, $res);
    exit;
}

// GET /api/list-tokens
if ($request_method === 'GET' && $path === '/api/list-tokens') {
    // Adapter: Transform response format (workspaces -> tokens)
    ob_start();
    [$req, $res] = create_objects([], $queryParams);
    $controller->listWorkspaces($req, $res);
    $output = ob_get_clean();

    $data = json_decode($output, true);
    if (isset($data['workspaces'])) {
        $data['tokens'] = $data['workspaces']; // Alias workspaces as tokens
        unset($data['workspaces']);
    }

    json_response($data);
    exit;
}

// POST /api/delete-token
if ($request_method === 'POST' && $path === '/api/delete-token') {
    // Adapter: Map POST body to DELETE method arguments
    $workspaceId = $request_json['workspace_id'] ?? '';

    [$req, $res] = create_objects([], $queryParams);
    $controller->deleteWorkspace($req, $res, ['workspace_id' => $workspaceId]);
    exit;
}

// POST /api/get-token
if ($request_method === 'POST' && $path === '/api/get-token') {
    // Adapter: Map to getConfiguration and extract token
    $workspaceId = $request_json['workspace_id'] ?? '';
    $app = $request_json['app'] ?? '';

    // Manually call helper since controller doesn't expose raw token getter easily via API
    // But we can use getConfiguration if it returns the token (it usually returns encrypted or masked?)
    // Let's try to use the database helper directly for this specific legacy requirement
    try {
        $config = $dbHelper->getConfiguration($app, $workspaceId);
        if ($config && isset($config['api_key'])) {
            // Decrypt if necessary, but getConfiguration usually returns decrypted if using helper?
            // Wait, NotionDatabaseHelper::getConfiguration returns decrypted array?
            // Let's check NotionDatabaseHelper.php
            // It calls $this->encryption->decrypt($row['access_token'])
            json_response(['success' => true, 'token' => $config['api_key']]);
        } else {
            json_response(['success' => false, 'error' => 'Token not found']);
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ============ UI Routes ============

// ============ UI Routes ============

// GET / - Main page (serve the complete widget from tests/index.php)
if ($request_method === 'GET' && ($request_uri === '/' || $request_uri === '/index.php')) {
    require __DIR__ . '/index.php';
    exit;
}

// GET /tests/index.php - Alias for the widget
if ($request_method === 'GET' && $request_uri === '/tests/index.php') {
    require __DIR__ . '/index.php';
    exit;
}

// 404
http_response_code(404);
json_response(['error' => 'Not Found'], 404);
