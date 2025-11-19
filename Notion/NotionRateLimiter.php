<?php
/**
 * NotionRateLimiter - Respects Notion API rate limits (3 requests/second = 180/minute)
 *
 * Tracks requests per app+workspace and applies backoff when approaching limits
 * Uses simple in-memory tracking - resets per application lifecycle
 * Conservative limit: 150 req/min (2.5 req/sec) leaves 0.5 req/sec safety margin
 */

namespace Notioneers\Shared\Notion;

class NotionRateLimiter {
    // Notion API actual limit: 3 requests/second = 180/minute
    // Using 150/minute = 2.5 req/second (safe margin below limit)
    private const RATE_LIMIT_REQUESTS_PER_MINUTE = 150;
    private const RATE_LIMIT_WINDOW_SECONDS = 60;

    /**
     * Request tracking: [app_workspace => [timestamp1, timestamp2, ...]]
     *
     * @var array<string, array<int, float>>
     */
    private array $requests = [];

    /**
     * Check if we should wait before making a request
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return void Sleeps if necessary
     * @throws \RuntimeException If rate limit persistently exceeded
     */
    public function waitIfNecessary(string $appName, string $workspaceId): void {
        $key = $this->getKey($appName, $workspaceId);
        $now = microtime(true);

        // Remove old requests outside the window
        if (isset($this->requests[$key])) {
            $this->requests[$key] = array_filter(
                $this->requests[$key],
                fn ($timestamp) => ($now - $timestamp) < self::RATE_LIMIT_WINDOW_SECONDS
            );
        }

        // Count recent requests
        $recentRequests = $this->requests[$key] ?? [];
        $requestCount = count($recentRequests);

        // Calculate time to wait if at limit
        if ($requestCount >= self::RATE_LIMIT_REQUESTS_PER_MINUTE) {
            // Find oldest request in the window
            $oldestRequest = min($recentRequests);
            $timeUntilExpiry = ($oldestRequest + self::RATE_LIMIT_WINDOW_SECONDS) - $now;

            if ($timeUntilExpiry > 0) {
                // Wait for the oldest request to expire (with small buffer)
                $waitTime = (int) (($timeUntilExpiry + 0.1) * 1000000); // Convert to microseconds
                usleep($waitTime);
            }
        }
    }

    /**
     * Record that a request was made
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return void
     */
    public function recordRequest(string $appName, string $workspaceId): void {
        $key = $this->getKey($appName, $workspaceId);

        if (!isset($this->requests[$key])) {
            $this->requests[$key] = [];
        }

        $this->requests[$key][] = microtime(true);
    }

    /**
     * Get current request count in the rolling window
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return int Number of requests in last minute
     */
    public function getCurrentRequestCount(string $appName, string $workspaceId): int {
        $key = $this->getKey($appName, $workspaceId);
        $now = microtime(true);

        if (!isset($this->requests[$key])) {
            return 0;
        }

        // Filter to only recent requests
        $recent = array_filter(
            $this->requests[$key],
            fn ($timestamp) => ($now - $timestamp) < self::RATE_LIMIT_WINDOW_SECONDS
        );

        return count($recent);
    }

    /**
     * Get percentage of rate limit used
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return float Percentage (0-100)
     */
    public function getLimitUsagePercent(string $appName, string $workspaceId): float {
        $current = $this->getCurrentRequestCount($appName, $workspaceId);
        return ($current / self::RATE_LIMIT_REQUESTS_PER_MINUTE) * 100;
    }

    /**
     * Reset tracking for app+workspace (useful for testing)
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return void
     */
    public function reset(string $appName, string $workspaceId): void {
        $key = $this->getKey($appName, $workspaceId);
        unset($this->requests[$key]);
    }

    /**
     * Clear all rate limit tracking
     *
     * @return void
     */
    public function clearAll(): void {
        $this->requests = [];
    }

    /**
     * Generate cache key
     *
     * @param string $appName Application name
     * @param string $workspaceId Workspace ID
     * @return string
     */
    private function getKey(string $appName, string $workspaceId): string {
        return "{$appName}:{$workspaceId}";
    }

    /**
     * Get statistics for monitoring
     *
     * @return array Statistics keyed by app:workspace
     */
    public function getStats(): array {
        $stats = [];
        $now = microtime(true);

        foreach ($this->requests as $key => $timestamps) {
            $recent = array_filter(
                $timestamps,
                fn ($timestamp) => ($now - $timestamp) < self::RATE_LIMIT_WINDOW_SECONDS
            );

            if (!empty($recent)) {
                $stats[$key] = [
                    'requests_in_window' => count($recent),
                    'limit_percent' => (count($recent) / self::RATE_LIMIT_REQUESTS_PER_MINUTE) * 100,
                ];
            }
        }

        return $stats;
    }
}
