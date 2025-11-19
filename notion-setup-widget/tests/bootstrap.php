<?php
/**
 * PHPUnit Bootstrap - Setup for NotionSetupWidget tests
 */

// Define paths - we're in shared/components/notion-setup-widget/tests/
define('WIDGET_ROOT', realpath(__DIR__ . '/..'));
define('COMPONENTS_ROOT', realpath(__DIR__ . '/../..'));
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

// Create test database
$testDbPath = sys_get_temp_dir() . '/notion-setup-widget-test.sqlite';
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

// Set environment
putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));

// Load Notion component autoloader
$notionPath = COMPONENTS_ROOT . '/Notion';
if (!is_dir($notionPath)) {
    // Fallback: try the old path
    $notionPath = COMPONENTS_ROOT . '/notion-api';
}

require_once $notionPath . '/NotionEncryption.php';
require_once $notionPath . '/NotionDatabaseHelper.php';
require_once $notionPath . '/NotionService.php';
require_once $notionPath . '/NotionServiceFactory.php';
require_once $notionPath . '/NotionCache.php';
require_once $notionPath . '/NotionRateLimiter.php';
require_once $notionPath . '/NotionApiException.php';
require_once $notionPath . '/NotionConfig.php';

// Load Setup Widget autoloader
require_once WIDGET_ROOT . '/NotionSetupWidget.php';
require_once WIDGET_ROOT . '/NotionSetupWidgetController.php';
require_once WIDGET_ROOT . '/NotionClient.php';
