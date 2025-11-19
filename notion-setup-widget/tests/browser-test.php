<?php
/**
 * Browser Test - Open this file in a browser to test the Setup Widget UI
 *
 * Run: php -S localhost:8080
 * Then visit: http://localhost:8080/tests/browser-test.php
 */

// Create in-memory database for testing
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Set encryption key
putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));

// Load components
require_once __DIR__ . '/bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;

$encryption = new NotionEncryption();
$widget = new NotionSetupWidget($pdo, $encryption, 'browser-test-app');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notion Setup Widget - Browser Test</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .test-info {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .test-info h2 {
            color: #667eea;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        .test-info ul {
            margin-bottom: 0;
            list-style: none;
            padding: 0;
        }
        .test-info li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        .test-info li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        .widget-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Header -->
        <div class="header">
            <h1>🔗 Notion Setup Widget</h1>
            <p>Browser Test - Visual & Functional Testing</p>
        </div>

        <!-- Test Information -->
        <div class="test-info">
            <h2>What to Test</h2>
            <ul>
                <li>Form input validation (try empty fields)</li>
                <li>Visual appearance (responsive design)</li>
                <li>Modal functionality (click "Configure")</li>
                <li>Button states and interactions</li>
                <li>Console for JavaScript errors (F12)</li>
                <li>Responsive design (resize window)</li>
            </ul>
        </div>

        <!-- Setup Widget -->
        <div class="widget-container">
            <?php echo $widget->render(); ?>
        </div>

        <!-- Debug Info -->
        <div class="test-info" style="margin-top: 2rem;">
            <h2>Debug Information</h2>
            <ul>
                <li><strong>App Name:</strong> <?php echo htmlspecialchars($widget->getAppName()); ?></li>
                <li><strong>Widget ID:</strong> <?php echo htmlspecialchars($widget->getWidgetId()); ?></li>
                <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                <li><strong>Encryption Key:</strong> Set ✓</li>
                <li><strong>Database:</strong> SQLite (in-memory) ✓</li>
            </ul>
        </div>

        <!-- Footer -->
        <div style="text-align: center; color: white; margin-top: 3rem;">
            <p>
                Open Browser Console (F12) to check for JavaScript errors
                <br>
                <small>Test app version 1.0.0</small>
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Debug Console -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✓ Setup Widget loaded successfully');
            console.log('✓ Bootstrap available:', typeof bootstrap !== 'undefined');
            console.log('✓ Test App: browser-test-app');

            // Patch console.error to catch widget errors
            const originalError = console.error;
            console.error = function(...args) {
                originalError.apply(console, ['⚠️ Widget Error:', ...args]);
                // Show error in alert for visibility
                if (args[0] && typeof args[0] === 'string') {
                    console.warn('Widget detected an error. Check console for details.');
                }
            };

            // Test that main functions exist
            window.addEventListener('error', function(e) {
                console.error('Global error caught:', e.message);
            });
        });

        // Log when modals are shown/hidden
        const configModal = document.getElementById('configModal');
        if (configModal) {
            configModal.addEventListener('show.bs.modal', function() {
                console.log('✓ Config modal opened');
            });
            configModal.addEventListener('hide.bs.modal', function() {
                console.log('✓ Config modal closed');
            });
        }
    </script>
</body>
</html>
