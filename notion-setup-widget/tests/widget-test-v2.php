<?php
/**
 * Notion Setup Widget V2 - Test/Demo Page
 *
 * Run: php -S localhost:8080
 * Visit: http://localhost:8080/tests/widget-test-v2.php
 */

// Setup
putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));
require_once __DIR__ . '/bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidgetV2;
use Notioneers\Shared\Notion\NotionEncryption;
use PDO;

// Setup database
$dbPath = sys_get_temp_dir() . '/notion-widget-v2-test.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$encryption = new NotionEncryption();
$dbHelper = new \Notioneers\Shared\Notion\NotionDatabaseHelper($pdo, $encryption);
$dbHelper->initializeDatabase();

// Create widget
$widget = new NotionSetupWidgetV2($pdo, $encryption, 'demo-app', 'widget-demo');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notion Setup Widget V2 - Demo</title>
    <!-- Notioneers Design System (Bootstrap 5.3 + Brand Colors) -->
    <link href="/shared/components/design-system/css/theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Notioneers Brand Colors */
        body {
            background-color: #F2F4F2;  /* Halo - Light background */
            min-height: 100vh;
            padding: 2rem 0;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: #063312;  /* Depth */
            font-weight: 500;
        }
        .header p {
            font-size: 1.1rem;
            color: #454F45;  /* Root */
        }
        .info-box {
            background: white;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(6, 51, 18, 0.08);
            border-left: 4px solid #92EF9A;  /* Bloom */
        }
        .widget-box {
            background: white;
            border-radius: 0.375rem;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(6, 51, 18, 0.08);
            margin-bottom: 2rem;
        }
        .note {
            background: #DEECDC;  /* Mist */
            border: 1px solid #AFCAAF;  /* Sage */
            color: #063312;  /* Depth */
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 2rem;
            border-left: 4px solid #92EF9A;  /* Bloom */
        }
        .info-box h3, .note strong {
            color: #063312;  /* Depth */
        }
        .info-box ul li {
            color: #454F45;  /* Root */
        }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Header -->
        <div class="header">
            <h1>🔗 Notion Setup Widget V2</h1>
            <p>Improved Version - Token Validation & Auto-Discovery</p>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <h3>✨ What's New in V2</h3>
            <ul class="mb-0">
                <li>✓ Real-time token validation</li>
                <li>✓ Automatic workspace discovery</li>
                <li>✓ Shows accessible pages & databases</li>
                <li>✓ Clean, working JavaScript (no syntax errors)</li>
                <li>✓ Based on proven CSV importer pattern</li>
                <li>✓ Better error handling & UX</li>
            </ul>
        </div>

        <!-- Widget -->
        <div class="widget-box">
            <?php echo $widget->render(); ?>
        </div>

        <!-- Instructions -->
        <div class="info-box">
            <h3>How to Test</h3>
            <ol>
                <li><strong>Enter a token:</strong> Type anything starting with at least 10 characters</li>
                <li><strong>Validation:</strong> Wait 1 second, it will show "Token is valid" if format is OK</li>
                <li><strong>Name field:</strong> Will auto-populate with workspace name</li>
                <li><strong>Connect:</strong> Click button to save the workspace</li>
                <li><strong>See it appear:</strong> Workspace shows immediately in the list below</li>
                <li><strong>Remove:</strong> Click "Remove" to delete workspace</li>
            </ol>
        </div>

        <!-- Note -->
        <div class="note">
            <strong>ℹ️ Test Tokens:</strong> For testing, any text with 10+ characters works. In production, tokens are validated against Notion API.
        </div>

        <!-- Footer -->
        <div style="text-align: center; color: white; margin-top: 3rem;">
            <p>
                <small>Notion Setup Widget V2 | Based on CSV Importer pattern</small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('✓ Widget V2 loaded');
        console.log('✓ Bootstrap available:', typeof bootstrap !== 'undefined');
    </script>
</body>
</html>
