<?php

namespace App\Support\Payments;

use RuntimeException;
use Throwable;

class MercadoPagoException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
