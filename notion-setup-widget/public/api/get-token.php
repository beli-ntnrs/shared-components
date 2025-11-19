<?php
/**
 * Get Token for Editing
 *
 * POST /api/get-token
 * Body: {
 *   "workspace_id": "...",
 *   "app": "app-name"
 * }
 *
 * Returns: { "success": true, "token": "ntn_...", "token_name": "..." }
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

    $workspaceId = $input['workspace_id'] ?? null;
    $appName = $input['app'] ?? null;

    // Validate inputs
    if (!$workspaceId || empty(trim($workspaceId))) {
        throw new Exception('Workspace ID is required');
    }

    if (!$appName || empty(trim($appName))) {
        throw new Exception('App name is required');
    }

    $workspaceId = trim($workspaceId);
    $appName = trim($appName);

    // Setup database
    $dbPath = sys_get_temp_dir() . '/notion-widget-working.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $encryption = new NotionEncryption();
    $dbHelper = new NotionDatabaseHelper($pdo, $encryption);

    // Initialize database if needed
    $dbHelper->initializeDatabase();

    // Get the token for this workspace
    $stmt = $pdo->prepare('
        SELECT api_key_encrypted, workspace_name
        FROM notion_credentials
        WHERE workspace_id = ? AND app_name = ?
        LIMIT 1
    ');
    $stmt->execute([$workspaceId, $appName]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception('Token not found');
    }

    // Decrypt the token
    $decryptedToken = $encryption->decrypt($record['api_key_encrypted']);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'token' => $decryptedToken,
        'token_name' => $record['workspace_name']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
