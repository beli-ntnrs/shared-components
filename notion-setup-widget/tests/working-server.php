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
function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// ============ API Routes ============

// Route to actual API files in public/api/ directory
$apiFile = null;

// POST /api/validate-token - Validate Notion token (real Notion API)
if ($request_method === 'POST' && $request_uri === '/api/validate-token') {
    $apiFile = __DIR__ . '/../public/api/validate-token.php';
}

// POST /api/save-token - Save token with user-provided name
if ($request_method === 'POST' && $request_uri === '/api/save-token') {
    $apiFile = __DIR__ . '/../public/api/save-token.php';
}

// GET /api/list-tokens - List all saved tokens for app
if ($request_method === 'GET' && $request_uri === '/api/list-tokens') {
    $apiFile = __DIR__ . '/../public/api/list-tokens.php';
}

// POST /api/delete-token - Delete a saved token
if ($request_method === 'POST' && $request_uri === '/api/delete-token') {
    $apiFile = __DIR__ . '/../public/api/delete-token.php';
}

// POST /api/get-token - Get token for editing (retrieve + decrypt)
if ($request_method === 'POST' && $request_uri === '/api/get-token') {
    $apiFile = __DIR__ . '/../public/api/get-token.php';
}

// If we have an API file to load, include it and exit
if ($apiFile && file_exists($apiFile)) {
    require $apiFile;
    exit;
}

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
