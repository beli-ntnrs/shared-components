<?php
/**
 * List Saved Tokens
 *
 * GET /api/list-tokens?app={appName}
 *
 * Returns: {
 *   "success": true,
 *   "tokens": [
 *     {
 *       "id": 1,
 *       "workspace_id": "...",
 *       "workspace_name": "My Workspace",
 *       "is_active": 1,
 *       "created_at": "2025-11-19...",
 *       "last_used_at": null
 *     }
 *   ]
 * }
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
    // Get app parameter
    $appName = $_GET['app'] ?? null;

    if (!$appName || empty(trim($appName))) {
        throw new Exception('App name is required');
    }

    $appName = trim($appName);

    // Setup database
    $dbPath = sys_get_temp_dir() . '/notion-widget-working.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $encryption = new NotionEncryption();
    $dbHelper = new NotionDatabaseHelper($pdo, $encryption);

    // Initialize database if needed
    $dbHelper->initializeDatabase();

    // List all tokens for this app
    $tokens = $dbHelper->listCredentials($appName);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'tokens' => $tokens
    ]);

} catch (Exception $e) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
