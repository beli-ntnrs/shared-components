<?php
/**
 * NotionCache - Simple in-memory cache for Notion API responses
 *
 * Helps reduce API calls and respects rate limits
 * Cache is per-app and expires after specified TTL
 */

namespace Notioneers\Shared\Notion;

class NotionCache {
    /**
     * Cache storage: [key => [value, expires_at]]
     *
     * @var array<string, array{0: mixed, 1: int}>
     */
    private array $cache = [];

    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get(string $key): mixed {
        if (!isset($this->cache[$key])) {
            return null;
        }

        [$value, $expiresAt] = $this->cache[$key];

        // Check if expired
        if ($expiresAt < time()) {
            unset($this->cache[$key]);
            return null;
        }

        return $value;
    }

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttlSeconds Time to live in seconds
     * @return void
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 300): void {
        $expiresAt = time() + $ttlSeconds;
        $this->cache[$key] = [$value, $expiresAt];
    }

    /**
     * Delete specific cache entry
     *
     * @param string $key Cache key
     * @return bool True if deleted, false if not found
     */
    public function delete(string $key): bool {
        if (!isset($this->cache[$key])) {
            return false;
        }

        unset($this->cache[$key]);
        return true;
    }

    /**
     * Clear all cache entries
     *
     * @return void
     */
    public function clear(): void {
        $this->cache = [];
    }

    /**
     * Cleanup expired entries
     *
     * @return int Number of entries cleaned
     */
    public function cleanup(): int {
        $count = 0;
        $now = time();

        foreach ($this->cache as $key => [$_, $expiresAt]) {
            if ($expiresAt < $now) {
                unset($this->cache[$key]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public function getStats(): array {
        $total = count($this->cache);
        $expired = 0;
        $now = time();

        foreach ($this->cache as $key => [$_, $expiresAt]) {
            if ($expiresAt < $now) {
                $expired++;
            }
        }

        return [
            'total_entries' => $total,
            'expired_entries' => $expired,
            'active_entries' => $total - $expired,
        ];
    }
}
