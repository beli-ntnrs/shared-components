<?php
/**
 * Unit Tests for NotionSetupWidget
 */

namespace Tests\Unit;

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use PHPUnit\Framework\TestCase;
use PDO;

class NotionSetupWidgetTest extends TestCase {
    private PDO $pdo;
    private NotionEncryption $encryption;
    private NotionSetupWidget $widget;

    protected function setUp(): void {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Initialize encryption
        $this->encryption = new NotionEncryption();

        // Create test widget
        $this->widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            'test-app'
        );
    }

    /**
     * Test that widget can be instantiated
     */
    public function testWidgetInstantiation(): void {
        $this->assertInstanceOf(NotionSetupWidget::class, $this->widget);
        $this->assertEquals('test-app', $this->widget->getAppName());
        $this->assertEquals('notion-setup-widget', $this->widget->getWidgetId());
    }

    /**
     * Test widget with custom ID
     */
    public function testWidgetWithCustomId(): void {
        $widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            'my-app',
            'custom-widget-id'
        );

        $this->assertEquals('custom-widget-id', $widget->getWidgetId());
    }

    /**
     * Test HTML rendering
     */
    public function testRenderHTML(): void {
        $html = $this->widget->render();

        // Check for essential HTML elements
        $this->assertStringContainsString('notion-setup-widget', $html);
        $this->assertStringContainsString('Notion Workspace Configuration', $html);
        $this->assertStringContainsString('Add New Workspace', $html);
        $this->assertStringContainsString('Connected Workspaces', $html);
    }

    /**
     * Test JavaScript is included in render
     */
    public function testRenderIncludesJavaScript(): void {
        $html = $this->widget->render();

        // Check for JavaScript code
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('loadWorkspaces', $html);
        $this->assertStringContainsString('handleAddWorkspace', $html);
    }

    /**
     * Test getWorkspacesJson method
     */
    public function testGetWorkspacesJson(): void {
        $json = $this->widget->getWorkspacesJson();

        // Should be valid JSON
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    /**
     * Test database is initialized
     */
    public function testDatabaseInitialized(): void {
        // This should not throw an exception
        $widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            'test-app-2'
        );

        // Check that table exists by querying it
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notion_credentials'");
        $this->assertNotEmpty($stmt->fetch());
    }

    /**
     * Test render contains input fields
     */
    public function testRenderContainsInputFields(): void {
        $html = $this->widget->render();

        // Check for form inputs
        $this->assertStringContainsString('id="workspace-name"', $html);
        $this->assertStringContainsString('id="api-key"', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }

    /**
     * Test render contains Bootstrap classes
     */
    public function testRenderContainsBootstrapClasses(): void {
        $html = $this->widget->render();

        // Check for Bootstrap 5 classes
        $this->assertStringContainsString('card', $html);
        $this->assertStringContainsString('btn', $html);
        $this->assertStringContainsString('form-control', $html);
        $this->assertStringContainsString('form-label', $html);
    }

    /**
     * Test HTML includes modal for configuration
     */
    public function testRenderIncludesConfigModal(): void {
        $html = $this->widget->render();

        // Check for configuration modal
        $this->assertStringContainsString('id="configModal"', $html);
        $this->assertStringContainsString('Configure Workspace', $html);
        $this->assertStringContainsString('id="database-select"', $html);
    }

    /**
     * Test widget app name is properly escaped in JavaScript
     */
    public function testWidgetEscapesAppNameInJavaScript(): void {
        // Create widget with special characters in app name
        $widget = new NotionSetupWidget(
            $this->pdo,
            $this->encryption,
            "test-app'"
        );

        $html = $widget->render();

        // Should contain escaped app name
        $this->assertStringContainsString("test-app&#039;", $html);
    }
}
