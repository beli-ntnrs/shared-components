<?php
/**
 * PHPUnit Bootstrap - Setup for Notion API tests
 */

// Autoload dependencies
require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

// Setup test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set default encryption key for tests
if (!getenv('ENCRYPTION_MASTER_KEY')) {
    putenv('ENCRYPTION_MASTER_KEY=test_key_' . bin2hex(random_bytes(16)));
}
