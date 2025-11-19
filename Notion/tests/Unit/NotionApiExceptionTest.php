<?php

namespace Tests\Unit\Notion;

use Notioneers\Shared\Notion\NotionApiException;
use PHPUnit\Framework\TestCase;

class NotionApiExceptionTest extends TestCase {
    public function testIsRetryable(): void {
        $rateLimited = new NotionApiException('Rate limited', NotionApiException::CODE_RATE_LIMITED);
        $this->assertTrue($rateLimited->isRetryable());

        $serverError = new NotionApiException('Server error', NotionApiException::CODE_SERVER_ERROR);
        $this->assertTrue($serverError->isRetryable());

        $networkError = new NotionApiException('Network error', NotionApiException::CODE_NETWORK_ERROR);
        $this->assertTrue($networkError->isRetryable());

        $unauthorized = new NotionApiException('Unauthorized', NotionApiException::CODE_UNAUTHORIZED);
        $this->assertFalse($unauthorized->isRetryable());
    }

    public function testIsAuthError(): void {
        $unauthorized = new NotionApiException('Unauthorized', NotionApiException::CODE_UNAUTHORIZED);
        $this->assertTrue($unauthorized->isAuthError());

        $forbidden = new NotionApiException('Forbidden', NotionApiException::CODE_FORBIDDEN);
        $this->assertTrue($forbidden->isAuthError());

        $notFound = new NotionApiException('Not found', NotionApiException::CODE_NOT_FOUND);
        $this->assertFalse($notFound->isAuthError());
    }

    public function testGetUserMessage(): void {
        $unauthorized = new NotionApiException('Unauthorized', NotionApiException::CODE_UNAUTHORIZED);
        $message = $unauthorized->getUserMessage();

        $this->assertStringContainsString('API key', $message);
        $this->assertStringContainsString('invalid', strtolower($message));
    }

    public function testGetHttpCode(): void {
        $exception = new NotionApiException('Error', NotionApiException::CODE_INVALID_REQUEST, 400);

        $this->assertEquals(400, $exception->getHttpCode());
    }

    public function testRateLimitedMessage(): void {
        $exception = new NotionApiException('Too many requests', NotionApiException::CODE_RATE_LIMITED);
        $message = $exception->getUserMessage();

        $this->assertStringContainsString('rate limit', strtolower($message));
    }

    public function testServerErrorMessage(): void {
        $exception = new NotionApiException('Internal server error', NotionApiException::CODE_SERVER_ERROR);
        $message = $exception->getUserMessage();

        $this->assertStringContainsString('server error', strtolower($message));
    }

    public function testNotFoundMessage(): void {
        $exception = new NotionApiException('Page not found', NotionApiException::CODE_NOT_FOUND);
        $message = $exception->getUserMessage();

        $this->assertStringContainsString('not found', strtolower($message));
    }
}
