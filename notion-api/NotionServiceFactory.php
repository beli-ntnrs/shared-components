<?php
/**
 * NotionServiceFactory - Factory for creating NotionService instances
 *
 * Handles initialization of all dependencies and provides easy setup
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionServiceFactory {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a NotionService instance for an app+workspace combination
     *
     * @param string $appName Application name
     * @param string $workspaceId Notion workspace ID
     * @return NotionService
     * @throws \RuntimeException If initialization fails
     */
    public function create(string $appName, string $workspaceId): NotionService {
        // Initialize encryption
        $encryption = new NotionEncryption();

        // Initialize database helper
        $dbHelper = new NotionDatabaseHelper($this->pdo, $encryption);

        // Ensure tables exist
        $dbHelper->initializeDatabase();

        // Create cache and rate limiter instances
        $cache = new NotionCache();
        $rateLimiter = new NotionRateLimiter();

        // Create and return NotionService
        return new NotionService($dbHelper, $cache, $rateLimiter, $appName, $workspaceId);
    }

    /**
     * Store new credentials and create service instance
     *
     * @param string $appName Application name
     * @param string $workspaceId Notion workspace ID
     * @param string $apiKey Notion API token (secret_xxx)
     * @param string|null $workspaceName Optional human-readable name
     * @return NotionService
     * @throws \RuntimeException If storage fails
     */
    public function createWithCredentials(
        string $appName,
        string $workspaceId,
        string $apiKey,
        ?string $workspaceName = null
    ): NotionService {
        // Initialize encryption
        $encryption = new NotionEncryption();

        // Initialize database helper
        $dbHelper = new NotionDatabaseHelper($this->pdo, $encryption);

        // Ensure tables exist
        $dbHelper->initializeDatabase();

        // Store credentials
        $dbHelper->storeCredentials($appName, $workspaceId, $apiKey, $workspaceName);

        // Create cache and rate limiter instances
        $cache = new NotionCache();
        $rateLimiter = new NotionRateLimiter();

        // Create and return NotionService
        return new NotionService($dbHelper, $cache, $rateLimiter, $appName, $workspaceId);
    }
}
