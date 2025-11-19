<?php
/**
 * PHPUnit Bootstrap - Setup for NotionSetupWidget tests
 */

// Define root path
define('ROOT_PATH', realpath(__DIR__ . '/../../..'));

// Create test database
$testDbPath = sys_get_temp_dir() . '/notion-setup-widget-test.sqlite';
if (file_exists($testDbPath)) {
    unlink($testDbPath);
}

// Set environment
putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));

// Load Notion component autoloader
require_once ROOT_PATH . '/shared/components/Notion/NotionEncryption.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionDatabaseHelper.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionService.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionServiceFactory.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionCache.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionRateLimiter.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionApiException.php';
require_once ROOT_PATH . '/shared/components/Notion/NotionConfig.php';

// Load Setup Widget autoloader
require_once ROOT_PATH . '/shared/components/notion-setup-widget/NotionSetupWidget.php';
require_once ROOT_PATH . '/shared/components/notion-setup-widget/NotionSetupWidgetController.php';
