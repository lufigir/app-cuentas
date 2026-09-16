<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class AccountsServiceUnavailable extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The accounts service is unavailable.', 503, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], 503);
    }
}
