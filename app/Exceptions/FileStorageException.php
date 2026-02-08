<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class FileStorageException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage() ?: 'File storage operation failed',
        ], 500);
    }
}
