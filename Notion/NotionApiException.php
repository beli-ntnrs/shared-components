<?php
/**
 * NotionApiException - Custom exception for Notion API errors
 *
 * Provides structured error handling for different API failure scenarios
 */

namespace Notioneers\Shared\Notion;

class NotionApiException extends \Exception {
    // Error codes
    public const CODE_INVALID_REQUEST = 400;
    public const CODE_UNAUTHORIZED = 401;
    public const CODE_FORBIDDEN = 403;
    public const CODE_NOT_FOUND = 404;
    public const CODE_CONFLICT = 409;
    public const CODE_RATE_LIMITED = 429;
    public const CODE_SERVER_ERROR = 500;
    public const CODE_NETWORK_ERROR = 1001;
    public const CODE_INVALID_RESPONSE = 1002;
    public const CODE_UNKNOWN_ERROR = 1000;

    private int $httpCode;

    /**
     * Create new exception
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param int $httpCode HTTP status code (optional)
     */
    public function __construct(string $message, int $code = self::CODE_UNKNOWN_ERROR, int $httpCode = 0) {
        parent::__construct($message, $code);
        $this->httpCode = $httpCode;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getHttpCode(): int {
        return $this->httpCode;
    }

    /**
     * Check if error is retryable
     *
     * @return bool True if the request can be retried
     */
    public function isRetryable(): bool {
        return in_array($this->code, [
            self::CODE_RATE_LIMITED,
            self::CODE_SERVER_ERROR,
            self::CODE_NETWORK_ERROR,
        ], true);
    }

    /**
     * Check if error is authentication-related
     *
     * @return bool
     */
    public function isAuthError(): bool {
        return in_array($this->code, [
            self::CODE_UNAUTHORIZED,
            self::CODE_FORBIDDEN,
        ], true);
    }

    /**
     * Get user-friendly error message
     *
     * @return string
     */
    public function getUserMessage(): string {
        return match ($this->code) {
            self::CODE_INVALID_REQUEST => 'Invalid request to Notion API. Please check your request parameters.',
            self::CODE_UNAUTHORIZED => 'Notion API key is invalid or expired. Please update your credentials.',
            self::CODE_FORBIDDEN => 'You do not have permission to access this Notion resource.',
            self::CODE_NOT_FOUND => 'The requested Notion resource was not found.',
            self::CODE_CONFLICT => 'Conflict with existing data. The resource may have been modified.',
            self::CODE_RATE_LIMITED => 'Notion API rate limit exceeded. Please try again in a few moments.',
            self::CODE_SERVER_ERROR => 'Notion API server error. Please try again later.',
            self::CODE_NETWORK_ERROR => 'Network error connecting to Notion API. Please check your connection.',
            self::CODE_INVALID_RESPONSE => 'Invalid response from Notion API.',
            default => 'An error occurred while communicating with Notion API.',
        };
    }
}
