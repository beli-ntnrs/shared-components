<?php
/**
 * NotionService - Main Notion API client with caching and rate limiting
 *
 * Provides methods for:
 * - Querying databases
 * - Reading/Writing pages
 * - Reading/Writing properties
 * - Block operations
 * - Automatic caching and rate limit handling
 */

namespace Notioneers\Shared\Notion;

use PDO;

class NotionService {
    private const NOTION_API_BASE = 'https://api.notion.com/v1';
    private const NOTION_API_VERSION = '2024-08-15';
    // Notion API: 3 requests/second = 180/minute
    // Using 150/minute = 2.5 req/second (safe margin below limit)
    private const RATE_LIMIT_REQUESTS_PER_MINUTE = 150;

    private NotionDatabaseHelper $dbHelper;
    private NotionCache $cache;
    private NotionRateLimiter $rateLimiter;

    private string $appName;
    private string $workspaceId;
    private string $apiKey;

    /**
     * Initialize NotionService
     *
     * @param NotionDatabaseHelper $dbHelper Database helper for credentials
     * @param NotionCache $cache Cache instance
     * @param NotionRateLimiter $rateLimiter Rate limiter instance
     * @param string $appName Application name (e.g., 'admintool')
     * @param string $workspaceId Notion workspace ID
     * @throws \RuntimeException If credentials not found
     */
    public function __construct(
        NotionDatabaseHelper $dbHelper,
        NotionCache $cache,
        NotionRateLimiter $rateLimiter,
        string $appName,
        string $workspaceId
    ) {
        $this->dbHelper = $dbHelper;
        $this->cache = $cache;
        $this->rateLimiter = $rateLimiter;
        $this->appName = $appName;
        $this->workspaceId = $workspaceId;

        // Load and decrypt API key
        $credentials = $this->dbHelper->getCredentials($appName, $workspaceId);
        $this->apiKey = $credentials['api_key'];
    }

    /**
     * Query a Notion database
     *
     * @param string $databaseId Notion database ID
     * @param array $filter Optional filter criteria
     * @param array $sorts Optional sort criteria
     * @param int $pageSize Number of results per page (1-100)
     * @param string|null $startCursor Pagination cursor
     * @return array Notion database query response
     * @throws NotionApiException On API error
     */
    public function queryDatabase(
        string $databaseId,
        array $filter = [],
        array $sorts = [],
        int $pageSize = 100,
        ?string $startCursor = null
    ): array {
        $cacheKey = "database_query:{$databaseId}:" . md5(json_encode(['filter' => $filter, 'sorts' => $sorts]));

        // Check cache first (5 minutes for database queries)
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $payload = [
            'page_size' => min(max($pageSize, 1), 100), // Clamp 1-100
        ];

        if (!empty($filter)) {
            $payload['filter'] = $filter;
        }

        if (!empty($sorts)) {
            $payload['sorts'] = $sorts;
        }

        if ($startCursor) {
            $payload['start_cursor'] = $startCursor;
        }

        $response = $this->makeApiRequest('POST', "/databases/{$databaseId}/query", $payload);

        // Cache the response
        $this->cache->set($cacheKey, $response, 300); // 5 minutes

        return $response;
    }

    /**
     * Get a page by ID
     *
     * @param string $pageId Notion page ID
     * @return array Page object
     * @throws NotionApiException On API error
     */
    public function getPage(string $pageId): array {
        $cacheKey = "page:{$pageId}";

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $response = $this->makeApiRequest('GET', "/pages/{$pageId}");

        $this->cache->set($cacheKey, $response, 600); // 10 minutes

        return $response;
    }

    /**
     * Update page properties
     *
     * @param string $pageId Notion page ID
     * @param array $properties Properties to update (format: ["Name" => ["title" => [["text" => ["content" => "value"]]]]])
     * @return array Updated page object
     * @throws NotionApiException On API error
     */
    public function updatePage(string $pageId, array $properties): array {
        $payload = ['properties' => $properties];

        $response = $this->makeApiRequest('PATCH', "/pages/{$pageId}", $payload);

        // Invalidate cache for this page
        $this->cache->delete("page:{$pageId}");

        return $response;
    }

    /**
     * Create a new page
     *
     * @param string $parentDatabaseId Parent database ID
     * @param array $properties Page properties
     * @param array|null $content Optional page content (blocks)
     * @return array Created page object
     * @throws NotionApiException On API error
     */
    public function createPage(
        string $parentDatabaseId,
        array $properties,
        ?array $content = null
    ): array {
        $payload = [
            'parent' => ['database_id' => $parentDatabaseId],
            'properties' => $properties,
        ];

        if ($content !== null) {
            $payload['children'] = $content;
        }

        return $this->makeApiRequest('POST', '/pages', $payload);
    }

    /**
     * Get page property value
     *
     * @param string $pageId Notion page ID
     * @param string $propertyId Property ID or name
     * @return array Property value
     * @throws NotionApiException On API error
     */
    public function getPageProperty(string $pageId, string $propertyId): array {
        $cacheKey = "page_property:{$pageId}:{$propertyId}";

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $response = $this->makeApiRequest('GET', "/pages/{$pageId}/properties/{$propertyId}");

        $this->cache->set($cacheKey, $response, 600); // 10 minutes

        return $response;
    }

    /**
     * Get page blocks (children)
     *
     * @param string $blockId Block ID (can be page ID)
     * @param int $pageSize Results per page (1-100)
     * @param string|null $startCursor Pagination cursor
     * @return array Blocks array
     * @throws NotionApiException On API error
     */
    public function getBlockChildren(
        string $blockId,
        int $pageSize = 100,
        ?string $startCursor = null
    ): array {
        $cacheKey = "blocks:{$blockId}";

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $url = "/blocks/{$blockId}/children?page_size=" . min(max($pageSize, 1), 100);

        if ($startCursor) {
            $url .= "&start_cursor=" . urlencode($startCursor);
        }

        $response = $this->makeApiRequest('GET', $url);

        $this->cache->set($cacheKey, $response, 600); // 10 minutes

        return $response;
    }

    /**
     * Append blocks to a page
     *
     * @param string $blockId Block ID (usually page ID)
     * @param array $children Array of blocks to append
     * @return array Response with created blocks
     * @throws NotionApiException On API error
     */
    public function appendBlockChildren(string $blockId, array $children): array {
        $payload = ['children' => $children];

        $response = $this->makeApiRequest('PATCH', "/blocks/{$blockId}/children", $payload);

        // Invalidate cache
        $this->cache->delete("blocks:{$blockId}");

        return $response;
    }

    /**
     * Search across workspace
     *
     * @param string $query Search query
     * @param string|null $sort Optional sort (relevance, last_edited_time)
     * @return array Search results
     * @throws NotionApiException On API error
     */
    public function search(string $query, ?string $sort = null): array {
        $cacheKey = "search:" . md5(json_encode(['query' => $query, 'sort' => $sort]));

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $payload = ['query' => $query];

        if ($sort) {
            $payload['sort'] = ['direction' => 'descending', 'timestamp' => $sort];
        }

        $response = $this->makeApiRequest('POST', '/search', $payload);

        // Cache search results for 5 minutes
        $this->cache->set($cacheKey, $response, 300);

        return $response;
    }

    /**
     * Make authenticated API request with rate limiting
     *
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $endpoint API endpoint
     * @param array|null $payload Request payload
     * @return array Parsed JSON response
     * @throws NotionApiException On API error
     * @throws \RuntimeException On rate limit exceeded
     */
    private function makeApiRequest(string $method, string $endpoint, ?array $payload = null): array {
        // Rate limiting - wait if necessary
        $this->rateLimiter->waitIfNecessary($this->appName, $this->workspaceId);

        $url = self::NOTION_API_BASE . $endpoint;

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Notion-Version: ' . self::NOTION_API_VERSION,
            'Content-Type: application/json',
            'User-Agent: Notioneers/1.0',
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($payload !== null && in_array($method, ['POST', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // Handle cURL errors
        if ($response === false) {
            throw new NotionApiException(
                "HTTP request failed: $curlError",
                NotionApiException::CODE_NETWORK_ERROR
            );
        }

        $data = json_decode($response, true);

        if ($data === null) {
            throw new NotionApiException(
                "Invalid JSON response from Notion API",
                NotionApiException::CODE_INVALID_RESPONSE
            );
        }

        // Handle Notion API errors
        if ($httpCode >= 400) {
            throw new NotionApiException(
                $this->parseErrorMessage($data),
                $this->mapHttpCodeToNotionError($httpCode),
                $httpCode
            );
        }

        // Record successful API call for rate limiting
        $this->rateLimiter->recordRequest($this->appName, $this->workspaceId);

        // Record credential usage for audit trail
        $this->dbHelper->recordCredentialUsage($this->appName, $this->workspaceId);

        return $data;
    }

    /**
     * Parse error message from Notion API response
     *
     * @param array $data Error response
     * @return string Human-readable error message
     */
    private function parseErrorMessage(array $data): string {
        if (isset($data['message'])) {
            return $data['message'];
        }

        if (isset($data['error']['message'])) {
            return $data['error']['message'];
        }

        return 'Unknown Notion API error';
    }

    /**
     * Map HTTP status codes to Notion error types
     *
     * @param int $httpCode HTTP status code
     * @return int Exception code
     */
    private function mapHttpCodeToNotionError(int $httpCode): int {
        return match ($httpCode) {
            400 => NotionApiException::CODE_INVALID_REQUEST,
            401 => NotionApiException::CODE_UNAUTHORIZED,
            403 => NotionApiException::CODE_FORBIDDEN,
            404 => NotionApiException::CODE_NOT_FOUND,
            409 => NotionApiException::CODE_CONFLICT,
            429 => NotionApiException::CODE_RATE_LIMITED,
            500, 502, 503, 504 => NotionApiException::CODE_SERVER_ERROR,
            default => NotionApiException::CODE_UNKNOWN_ERROR,
        };
    }

    /**
     * Query database with automatic pagination (Generator)
     *
     * Convenience method that handles pagination automatically.
     * Use this for iterating through all pages in a database.
     *
     * @param string $databaseId Notion database ID
     * @param array $filter Optional filter criteria
     * @param array $sorts Optional sort criteria
     * @return \Generator Yields pages one at a time
     * @throws NotionApiException On API error
     *
     * @example
     * foreach ($service->queryDatabasePages($databaseId) as $page) {
     *     echo "Page ID: " . $page['id'] . "\n";
     * }
     */
    public function queryDatabasePages(
        string $databaseId,
        array $filter = [],
        array $sorts = []
    ): \Generator {
        $cursor = null;

        do {
            $result = $this->queryDatabase($databaseId, $filter, $sorts, 100, $cursor);

            foreach ($result['results'] ?? [] as $page) {
                yield $page;
            }

            $cursor = $result['next_cursor'] ?? null;
        } while ($cursor !== null);
    }
}
