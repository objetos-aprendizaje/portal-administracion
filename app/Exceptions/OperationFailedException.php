<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class OperationFailedException extends Exception
{
    protected $errorCode;

    public function __construct($message = "", $errorCode = 406, $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->errorCode);
    }
}
