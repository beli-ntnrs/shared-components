<?php

namespace Tests\Unit\Notion;

use Notioneers\Shared\Notion\NotionRateLimiter;
use PHPUnit\Framework\TestCase;

class NotionRateLimiterTest extends TestCase {
    private NotionRateLimiter $limiter;

    protected function setUp(): void {
        $this->limiter = new NotionRateLimiter();
    }

    public function testRecordRequest(): void {
        $this->limiter->recordRequest('app1', 'workspace1');

        $count = $this->limiter->getCurrentRequestCount('app1', 'workspace1');

        $this->assertEquals(1, $count);
    }

    public function testGetLimitUsagePercent(): void {
        // Make 30 requests (50% of 60 limit)
        for ($i = 0; $i < 30; $i++) {
            $this->limiter->recordRequest('app1', 'workspace1');
        }

        $percent = $this->limiter->getLimitUsagePercent('app1', 'workspace1');

        $this->assertGreaterThanOrEqual(49, $percent);
        $this->assertLessThanOrEqual(51, $percent);
    }

    public function testSeparateTracking(): void {
        $this->limiter->recordRequest('app1', 'workspace1');
        $this->limiter->recordRequest('app2', 'workspace2');
        $this->limiter->recordRequest('app1', 'workspace1');

        $this->assertEquals(2, $this->limiter->getCurrentRequestCount('app1', 'workspace1'));
        $this->assertEquals(1, $this->limiter->getCurrentRequestCount('app2', 'workspace2'));
    }

    public function testReset(): void {
        $this->limiter->recordRequest('app1', 'workspace1');

        $this->assertEquals(1, $this->limiter->getCurrentRequestCount('app1', 'workspace1'));

        $this->limiter->reset('app1', 'workspace1');

        $this->assertEquals(0, $this->limiter->getCurrentRequestCount('app1', 'workspace1'));
    }

    public function testClearAll(): void {
        $this->limiter->recordRequest('app1', 'workspace1');
        $this->limiter->recordRequest('app2', 'workspace2');

        $this->limiter->clearAll();

        $this->assertEquals(0, $this->limiter->getCurrentRequestCount('app1', 'workspace1'));
        $this->assertEquals(0, $this->limiter->getCurrentRequestCount('app2', 'workspace2'));
    }

    public function testGetStats(): void {
        $this->limiter->recordRequest('app1', 'workspace1');
        $this->limiter->recordRequest('app1', 'workspace1');
        $this->limiter->recordRequest('app2', 'workspace2');

        $stats = $this->limiter->getStats();

        $this->assertArrayHasKey('app1:workspace1', $stats);
        $this->assertArrayHasKey('app2:workspace2', $stats);

        $this->assertEquals(2, $stats['app1:workspace1']['requests_in_window']);
        $this->assertEquals(1, $stats['app2:workspace2']['requests_in_window']);
    }

    public function testWaitIfNecessaryDoesNotWaitWhenUnderLimit(): void {
        $start = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->limiter->waitIfNecessary('app1', 'workspace1');
            $this->limiter->recordRequest('app1', 'workspace1');
        }

        $elapsed = microtime(true) - $start;

        // Should complete quickly without waiting
        $this->assertLessThan(1, $elapsed);
    }

    public function testNonExistentKeyReturnsZero(): void {
        $count = $this->limiter->getCurrentRequestCount('nonexistent', 'workspace');

        $this->assertEquals(0, $count);
    }
}
