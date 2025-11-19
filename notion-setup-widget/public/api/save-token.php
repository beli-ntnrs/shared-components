<?php
/**
 * Save Notion Token
 *
 * POST /api/save-token
 * Body: {
 *   "token": "ntn_...",
 *   "token_name": "My Workspace",
 *   "app": "app-name"
 * }
 *
 * Returns: { "success": true, "token_id": 1, "message": "Token saved" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once __DIR__ . '/../../../Notion/NotionService.php';

use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;

ob_end_clean();
ob_start();

header('Content-Type: application/json');

try {
    // Parse request
    $input = json_decode(file_get_contents('php://input'), true);

    $token = $input['token'] ?? null;
    $tokenName = $input['token_name'] ?? null;
    $appName = $input['app'] ?? null;

    // Validate inputs
    if (!$token || empty(trim($token))) {
        throw new Exception('Token is required');
    }

    if (!$tokenName || empty(trim($tokenName))) {
        throw new Exception('Token name is required');
    }

    if (!$appName || empty(trim($appName))) {
        throw new Exception('App name is required');
    }

    $token = trim($token);
    $tokenName = trim($tokenName);
    $appName = trim($appName);

    // Validate token format
    if (strlen($token) < 15) {
        throw new Exception('Token is too short');
    }

    // Setup database
    $dbPath = sys_get_temp_dir() . '/notion-widget-working.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $encryption = new NotionEncryption();
    $dbHelper = new NotionDatabaseHelper($pdo, $encryption);

    // Initialize database if needed
    $dbHelper->initializeDatabase();

    // Generate workspace_id from app + token_name (unique identifier)
    $workspaceId = strtolower(preg_replace('/[^a-z0-9]+/', '-', $appName . '-' . $tokenName . '-' . uniqid()));

    // Store the token
    $tokenId = $dbHelper->storeCredentials(
        $appName,
        $workspaceId,
        $token,
        $tokenName
    );

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'token_id' => $tokenId,
        'workspace_id' => $workspaceId,
        'message' => 'Token saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
