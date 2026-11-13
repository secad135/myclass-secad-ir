<?php

namespace App\Exceptions;

use RuntimeException;

class UploadRejectedException extends RuntimeException
{
    public function __construct(string $message, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
