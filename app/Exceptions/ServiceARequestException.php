<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceARequestException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $statusCode,
        protected string $responseBody = ''
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function responseBody(): string
    {
        return $this->responseBody;
    }
}
