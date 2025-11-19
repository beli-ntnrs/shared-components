<?php
/**
 * NotionDatabaseHelper - Helper for database initialization and queries
 *
 * Handles database setup, credential storage/retrieval with encryption
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionDatabaseHelper {
    private PDO $pdo;
    private NotionEncryption $encryption;

    public function __construct(PDO $pdo, NotionEncryption $encryption) {
        $this->pdo = $pdo;
        $this->encryption = $encryption;
    }

    /**
     * Initialize database - create tables if they don't exist
     * Also applies any pending migrations
     *
     * @return void
     * @throws \RuntimeException If table creation fails
     */
    public function initializeDatabase(): void {
        $sqlFile = __DIR__ . '/CreateNotionCredentialsTable.sql';

        if (!file_exists($sqlFile)) {
            throw new \RuntimeException("Migration file not found: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);

        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database initialization failed: " . $e->getMessage());
        }

        // Apply any pending migrations
        $this->applyMigrations();
    }

    /**
     * Apply pending migrations to the database
     *
     * @return void
     * @throws \RuntimeException If migration fails
     */
    private function applyMigrations(): void {
        try {
            // Add missing columns if they don't exist
            $this->addColumnIfNotExists('notion_database_id', 'TEXT');
            $this->addColumnIfNotExists('notion_page_id', 'TEXT');
            $this->addColumnIfNotExists('config', 'TEXT');

            // Create indexes for new columns
            $this->pdo->exec(<<<SQL
                CREATE INDEX IF NOT EXISTS idx_notion_credentials_database_id
                ON notion_credentials(notion_database_id)
            SQL);

            $this->pdo->exec(<<<SQL
                CREATE INDEX IF NOT EXISTS idx_notion_credentials_page_id
                ON notion_credentials(notion_page_id)
            SQL);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Add a column to notion_credentials table if it doesn't exist
     * SQLite workaround since ALTER TABLE IF NOT EXISTS is not supported
     *
     * @param string $columnName Column name
     * @param string $columnType Column type (e.g., 'TEXT', 'INTEGER')
     * @return void
     */
    private function addColumnIfNotExists(string $columnName, string $columnType): void {
        try {
            // Try to query the column - if it fails, the column doesn't exist
            $this->pdo->query("SELECT $columnName FROM notion_credentials LIMIT 1");
        } catch (\PDOException $e) {
            // Column doesn't exist, add it
            $this->pdo->exec(<<<SQL
                ALTER TABLE notion_credentials ADD COLUMN $columnName $columnType
            SQL);
        }
    }

    /**
     * Store Notion API credentials (encrypted)
     *
     * @param string $appName Application name (e.g., 'admintool')
     * @param string $workspaceId Notion workspace ID
     * @param string $apiKey Plain text Notion API token
     * @param string|null $workspaceName Optional human-readable workspace name
     * @return int The credential ID
     * @throws \PDOException If storage fails
     */
    public function storeCredentials(
        string $appName,
        string $workspaceId,
        string $apiKey,
        ?string $workspaceName = null
    ): int {
        // Validate inputs
        if (empty($appName) || empty($workspaceId) || empty($apiKey)) {
            throw new \InvalidArgumentException('App name, workspace ID, and API key are required');
        }

        // Accept both old (ntn_) and new (secret_) Notion API token formats
        if (!preg_match('/^(secret_|ntn_)/', $apiKey)) {
            throw new \InvalidArgumentException('Invalid Notion API key format. Must start with secret_ or ntn_');
        }

        // Encrypt the API key
        $encryptedKey = $this->encryption->encrypt($apiKey);

        // Insert or update credentials
        $query = <<<SQL
            INSERT INTO notion_credentials (app_name, workspace_id, api_key_encrypted, workspace_name)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(app_name, workspace_id) DO UPDATE SET
                api_key_encrypted = excluded.api_key_encrypted,
                workspace_name = excluded.workspace_name,
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName, $workspaceId, $encryptedKey, $workspaceName]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Retrieve decrypted Notion API credentials
     *
     * @param string $appName Application name
     * @param string $workspaceId Notion workspace ID
     * @return array{api_key: string, workspace_name: string|null} Decrypted credentials
     * @throws \RuntimeException If credentials not found or decryption fails
     */
    public function getCredentials(string $appName, string $workspaceId): array {
        $query = <<<SQL
            SELECT api_key_encrypted, workspace_name
            FROM notion_credentials
            WHERE app_name = ? AND workspace_id = ? AND is_active = 1
            LIMIT 1
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName, $workspaceId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException(
                "No active credentials found for app '$appName' in workspace '$workspaceId'"
            );
        }

        try {
            $decryptedKey = $this->encryption->decrypt($row['api_key_encrypted']);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(
                "Failed to decrypt credentials: " . $e->getMessage()
            );
        }

        return [
            'api_key' => $decryptedKey,
            'workspace_name' => $row['workspace_name'],
        ];
    }

    /**
     * Get all credentials for an app
     *
     * @param string $appName Application name
     * @return array Array of workspace info (without decrypted keys for security)
     */
    public function listCredentials(string $appName): array {
        $query = <<<SQL
            SELECT id, workspace_id, workspace_name, is_active, created_at, last_used_at
            FROM notion_credentials
            WHERE app_name = ?
            ORDER BY workspace_name ASC
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Disable credentials (soft delete)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return bool True if disabled
     */
    public function disableCredentials(string $appName, string $workspaceId): bool {
        $query = <<<SQL
            UPDATE notion_credentials
            SET is_active = 0, updated_at = CURRENT_TIMESTAMP
            WHERE app_name = ? AND workspace_id = ?
        SQL;

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$appName, $workspaceId]);
    }

    /**
     * Update last_used timestamp (for audit trail)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return void
     */
    public function recordCredentialUsage(string $appName, string $workspaceId): void {
        $query = <<<SQL
            UPDATE notion_credentials
            SET last_used_at = CURRENT_TIMESTAMP
            WHERE app_name = ? AND workspace_id = ?
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName, $workspaceId]);
    }

    /**
     * Delete credentials completely (hard delete - use carefully!)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return bool True if deleted
     */
    public function deleteCredentials(string $appName, string $workspaceId): bool {
        $query = <<<SQL
            DELETE FROM notion_credentials
            WHERE app_name = ? AND workspace_id = ?
        SQL;

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$appName, $workspaceId]);
    }

    /**
     * Update workspace configuration (target database/page and custom config)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @param string|null $databaseId Target Notion database ID
     * @param string|null $pageId Target Notion page ID
     * @param array|null $config Custom app-specific configuration (will be stored as JSON)
     * @return bool True if updated
     */
    public function updateConfiguration(
        string $appName,
        string $workspaceId,
        ?string $databaseId = null,
        ?string $pageId = null,
        ?array $config = null
    ): bool {
        $configJson = $config ? json_encode($config) : null;

        $query = <<<SQL
            UPDATE notion_credentials
            SET notion_database_id = ?, notion_page_id = ?, config = ?, updated_at = CURRENT_TIMESTAMP
            WHERE app_name = ? AND workspace_id = ?
        SQL;

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            $databaseId,
            $pageId,
            $configJson,
            $appName,
            $workspaceId,
        ]);
    }

    /**
     * Get workspace configuration (target database/page and custom config)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return array{database_id: string|null, page_id: string|null, config: array|null}
     * @throws \RuntimeException If credentials not found
     */
    public function getConfiguration(string $appName, string $workspaceId): array {
        $query = <<<SQL
            SELECT notion_database_id, notion_page_id, config
            FROM notion_credentials
            WHERE app_name = ? AND workspace_id = ? AND is_active = 1
            LIMIT 1
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName, $workspaceId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException(
                "No active credentials found for app '$appName' in workspace '$workspaceId'"
            );
        }

        return [
            'database_id' => $row['notion_database_id'],
            'page_id' => $row['notion_page_id'],
            'config' => $row['config'] ? json_decode($row['config'], true) : null,
        ];
    }

    /**
     * Get full workspace info including credentials and configuration
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return array Complete workspace info (without encrypted API key)
     * @throws \RuntimeException If credentials not found
     */
    public function getWorkspaceInfo(string $appName, string $workspaceId): array {
        $query = <<<SQL
            SELECT id, workspace_id, workspace_name, notion_database_id, notion_page_id,
                   config, is_active, created_at, updated_at, last_used_at
            FROM notion_credentials
            WHERE app_name = ? AND workspace_id = ? AND is_active = 1
            LIMIT 1
        SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$appName, $workspaceId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException(
                "No active credentials found for app '$appName' in workspace '$workspaceId'"
            );
        }

        return [
            'id' => $row['id'],
            'workspace_id' => $row['workspace_id'],
            'workspace_name' => $row['workspace_name'],
            'database_id' => $row['notion_database_id'],
            'page_id' => $row['notion_page_id'],
            'config' => $row['config'] ? json_decode($row['config'], true) : null,
            'is_active' => $row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'last_used_at' => $row['last_used_at'],
        ];
    }
}
