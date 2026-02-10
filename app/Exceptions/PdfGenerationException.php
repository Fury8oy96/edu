<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PdfGenerationException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage() ?: 'PDF generation failed',
        ], 500);
    }
}
