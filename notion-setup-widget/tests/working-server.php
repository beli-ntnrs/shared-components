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

// GET /api/notion/credentials/{id}
if ($request_method === 'GET' && preg_match('#^/api/notion/credentials/([^/]+)$#', $path, $matches)) {
    [$req, $res] = create_objects([], $queryParams);
    $controller->getConfiguration($req, $res, ['workspace_id' => $matches[1]]);
    exit;
}

// ============ UI Routes ============

// ============ UI Routes ============

// Catch-all: Serve index.php for any other request (SPA-like behavior)
if ($request_method === 'GET' || $request_method === 'HEAD') {
    require __DIR__ . '/index.php';
    exit;
}

// 404 (only if not GET)
http_response_code(404);
json_response(['error' => 'Not Found'], 404);
