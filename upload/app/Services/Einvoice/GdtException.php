<?php

namespace App\Services\Einvoice;

use RuntimeException;

/** Error returned by the tax portal API. */
class GdtException extends RuntimeException
{
    public const AUTH = 'auth';            // token expired / wrong login
    public const RATE_LIMIT = 'rate_limit'; // HTTP 429
    public const NOT_FOUND = 'not_found';  // HTTP 500 on export-xml: original file does not exist
    public const NETWORK = 'network';      // cannot connect / timeout
    public const OTHER = 'other';

    public function __construct(string $message, public readonly string $kind = self::OTHER, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
