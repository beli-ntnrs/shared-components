<?php

namespace Tests\Unit\Notion;

use Notioneers\Shared\Notion\NotionCache;
use PHPUnit\Framework\TestCase;

class NotionCacheTest extends TestCase {
    private NotionCache $cache;

    protected function setUp(): void {
        $this->cache = new NotionCache();
    }

    public function testSetAndGet(): void {
        $this->cache->set('key1', 'value1', 300);

        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetNonExistent(): void {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function testExpiration(): void {
        // Set with 1 second TTL
        $this->cache->set('expiring', 'value', 1);

        $this->assertEquals('value', $this->cache->get('expiring'));

        // Wait for expiration
        sleep(2);

        $this->assertNull($this->cache->get('expiring'));
    }

    public function testDelete(): void {
        $this->cache->set('key', 'value', 300);
        $this->assertTrue($this->cache->delete('key'));

        $this->assertNull($this->cache->get('key'));
        $this->assertFalse($this->cache->delete('key'));
    }

    public function testClear(): void {
        $this->cache->set('key1', 'value1', 300);
        $this->cache->set('key2', 'value2', 300);

        $this->cache->clear();

        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    public function testCleanup(): void {
        $this->cache->set('key1', 'value1', 1);
        $this->cache->set('key2', 'value2', 300);

        sleep(2);

        $cleaned = $this->cache->cleanup();

        $this->assertGreaterThan(0, $cleaned);
        $this->assertNull($this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }

    public function testStats(): void {
        $this->cache->set('key1', 'value1', 300);
        $this->cache->set('key2', 'value2', 1);

        $stats = $this->cache->getStats();

        $this->assertEquals(2, $stats['total_entries']);
        $this->assertEquals(2, $stats['active_entries']);

        sleep(2);

        $stats = $this->cache->getStats();

        // One should have expired
        $this->assertGreaterThanOrEqual(1, $stats['expired_entries']);
    }

    public function testArrayCaching(): void {
        $data = [
            'id' => '123',
            'name' => 'Test',
            'nested' => ['key' => 'value'],
        ];

        $this->cache->set('complex', $data, 300);

        $cached = $this->cache->get('complex');

        $this->assertEquals($data, $cached);
    }
}
