<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Google Sheets request or configuration problem. Messages never contain credentials, tokens or
 * participant data: only the HTTP status and Google's short error message.
 */
class GoogleSheetsException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }

    public static function configuration(string $problem): self
    {
        return new self("Google Sheets is not configured correctly: {$problem}");
    }

    /**
     * Worth trying again later: rate limits, Google server errors and connection problems.
     * Configuration, permission and request errors need someone to fix them first.
     */
    public function isTemporary(): bool
    {
        return $this->status === null ? str_starts_with($this->getMessage(), 'Could not reach Google') : ($this->status === 429 || $this->status >= 500);
    }
}
