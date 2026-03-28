<?php

namespace framework\web\exceptions;

use Exception;
use Throwable;

abstract class HttpException extends Exception {
    public int $status;

    public function __construct(string $message = "", int $code = 0, Throwable|null $previous = null)
    {
        $this->status = $code;
        return parent::__construct($message, $code, $previous);
    }
}