<?php

declare(strict_types=1);

namespace EdgeCron;

final class APIError extends \RuntimeException
{
    public function __construct(
        public readonly int $codeValue,
        string $message,
        public readonly string $requestId
    ) {
        parent::__construct(sprintf('edgecron: code=%d message=%s request_id=%s', $codeValue, $message, $requestId));
    }
}
