<?php
/**
 * NotionClient - Simplified client for multi-tenant Notion API access
 *
 * Wrapper around NotionServiceFactory that provides:
 * - Automatic token lookup from workspace configuration
 * - Simplified multi-workspace switching
 * - Configuration access
 * - Cache invalidation per workspace
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionClient {
    private string $appName;
    private NotionServiceFactory $serviceFactory;
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;
    private ?string $currentWorkspace = null;
    private array $serviceCache = [];

    /**
     * Initialize NotionClient for an app
     *
     * @param PDO $pdo Database connection
     * @param string $appName App identifier (e.g., 'csv-importer')
     */
    public function __construct(PDO $pdo, string $appName) {
        $this->appName = $appName;
        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($pdo, $this->encryption);
        $this->serviceFactory = new NotionServiceFactory($pdo);

        // Ensure database is initialized
        $this->dbHelper->initializeDatabase();
    }

    /**
     * Set the current workspace for subsequent operations
     *
     * @param string $workspaceId Notion workspace ID
     * @return self For chaining
     * @throws \RuntimeException If workspace not found
     */
    public function setWorkspace(string $workspaceId): self {
        // Verify workspace exists
        try {
            $this->dbHelper->getCredentials($this->appName, $workspaceId);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(
                "Workspace '$workspaceId' not found for app '{$this->appName}'"
            );
        }

        $this->currentWorkspace = $workspaceId;
        return $this;
    }

    /**
     * Get current workspace ID
     *
     * @return string|null
     */
    public function getCurrentWorkspace(): ?string {
        return $this->currentWorkspace;
    }

    /**
     * Get NotionService for current workspace
     *
     * @return NotionService
     * @throws \RuntimeException If no workspace is set
     */
    public function getService(): NotionService {
        if (!$this->currentWorkspace) {
            throw new \RuntimeException('No workspace selected. Call setWorkspace() first.');
        }

        // Return cached service if available
        $cacheKey = "{$this->appName}:{$this->currentWorkspace}";
        if (isset($this->serviceCache[$cacheKey])) {
            return $this->serviceCache[$cacheKey];
        }

        // Create and cache service
        $service = $this->serviceFactory->create($this->appName, $this->currentWorkspace);
        $this->serviceCache[$cacheKey] = $service;

        return $service;
    }

    /**
     * Query a database
     *
     * @param string|null $databaseId If null, uses configured database_id
     * @param array $filter Notion filter object
     * @param array $sorts Sort configuration
     * @return array Query results
     * @throws \RuntimeException If no database configured or workspace set
     */
    public function queryDatabase(
        ?string $databaseId = null,
        array $filter = [],
        array $sorts = []
    ): array {
        $service = $this->getService();

        // Use provided database or fetch from configuration
        if (!$databaseId) {
            $config = $this->getConfiguration();
            $databaseId = $config['database_id'];

            if (!$databaseId) {
                throw new \RuntimeException(
                    'No target database configured for this workspace. ' .
                    'Use the Setup Widget to configure a database.'
                );
            }
        }

        return $service->queryDatabase($databaseId, $filter, $sorts);
    }

    /**
     * Create a page in a database
     *
     * @param array $properties Page properties
     * @param string|null $databaseId If null, uses configured database_id
     * @return array Created page
     * @throws \RuntimeException If no database configured or workspace set
     */
    public function createPage(array $properties, ?string $databaseId = null): array {
        $service = $this->getService();

        // Use provided database or fetch from configuration
        if (!$databaseId) {
            $config = $this->getConfiguration();
            $databaseId = $config['database_id'];

            if (!$databaseId) {
                throw new \RuntimeException(
                    'No target database configured for this workspace. ' .
                    'Use the Setup Widget to configure a database.'
                );
            }
        }

        return $service->createPage($databaseId, $properties);
    }

    /**
     * Get a page
     *
     * @param string $pageId Page ID
     * @return array Page data
     */
    public function getPage(string $pageId): array {
        return $this->getService()->getPage($pageId);
    }

    /**
     * Update a page
     *
     * @param string $pageId Page ID
     * @param array $properties Properties to update
     * @return array Updated page
     */
    public function updatePage(string $pageId, array $properties): array {
        return $this->getService()->updatePage($pageId, $properties);
    }

    /**
     * Search across workspace
     *
     * @param string $query Search query
     * @param string $sort Sort order ('relevance' or 'last_edited_time')
     * @param string|null $filter Filter by object type ('database', 'page', etc.)
     * @return array Search results
     */
    public function search(
        string $query,
        string $sort = 'relevance',
        ?string $filter = null
    ): array {
        return $this->getService()->search($query, $sort, $filter);
    }

    /**
     * Get block children
     *
     * @param string $blockId Block ID (usually a page ID)
     * @return array Blocks
     */
    public function getBlockChildren(string $blockId): array {
        return $this->getService()->getBlockChildren($blockId);
    }

    /**
     * Append blocks to a page
     *
     * @param string $pageId Page ID
     * @param array $blocks Blocks to append
     * @return array Appended blocks
     */
    public function appendBlockChildren(string $pageId, array $blocks): array {
        return $this->getService()->appendBlockChildren($pageId, $blocks);
    }

    /**
     * Get workspace configuration
     *
     * @return array Configuration with database_id, page_id, config
     * @throws \RuntimeException If no workspace set
     */
    public function getConfiguration(): array {
        if (!$this->currentWorkspace) {
            throw new \RuntimeException('No workspace selected. Call setWorkspace() first.');
        }

        return $this->dbHelper->getConfiguration($this->appName, $this->currentWorkspace);
    }

    /**
     * Get full workspace information
     *
     * @return array Complete workspace info
     * @throws \RuntimeException If no workspace set
     */
    public function getWorkspaceInfo(): array {
        if (!$this->currentWorkspace) {
            throw new \RuntimeException('No workspace selected. Call setWorkspace() first.');
        }

        return $this->dbHelper->getWorkspaceInfo($this->appName, $this->currentWorkspace);
    }

    /**
     * Get all available workspaces
     *
     * @return array List of workspaces
     */
    public function getWorkspaces(): array {
        return $this->dbHelper->listCredentials($this->appName);
    }

    /**
     * Update configuration for current workspace
     *
     * @param string|null $databaseId Target database ID
     * @param string|null $pageId Target page ID
     * @param array|null $config Custom configuration
     * @return bool Success
     * @throws \RuntimeException If no workspace set
     */
    public function updateConfiguration(
        ?string $databaseId = null,
        ?string $pageId = null,
        ?array $config = null
    ): bool {
        if (!$this->currentWorkspace) {
            throw new \RuntimeException('No workspace selected. Call setWorkspace() first.');
        }

        return $this->dbHelper->updateConfiguration(
            $this->appName,
            $this->currentWorkspace,
            $databaseId,
            $pageId,
            $config
        );
    }

    /**
     * Clear service cache
     *
     * @param string|null $workspaceId If null, clears all cache
     * @return void
     */
    public function clearCache(?string $workspaceId = null): void {
        if ($workspaceId) {
            $cacheKey = "{$this->appName}:{$workspaceId}";
            unset($this->serviceCache[$cacheKey]);
        } else {
            $this->serviceCache = [];
        }
    }

    /**
     * Record workspace usage (for audit trail)
     *
     * @return self For chaining
     * @throws \RuntimeException If no workspace set
     */
    public function recordUsage(): self {
        if (!$this->currentWorkspace) {
            throw new \RuntimeException('No workspace selected. Call setWorkspace() first.');
        }

        $this->dbHelper->recordCredentialUsage($this->appName, $this->currentWorkspace);
        return $this;
    }

    /**
     * Get app name
     *
     * @return string
     */
    public function getAppName(): string {
        return $this->appName;
    }
}
