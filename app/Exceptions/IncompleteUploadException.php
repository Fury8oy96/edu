<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when attempting to complete an upload with missing chunks.
 */
class IncompleteUploadException extends VideoException
{
    protected array $missingChunks;

    public function __construct(array $missingChunks = [])
    {
        $this->missingChunks = $missingChunks;
        
        $message = empty($missingChunks)
            ? "Not all chunks have been received"
            : "Missing chunks: " . implode(', ', $missingChunks);
            
        parent::__construct($message, 400);
    }

    public function getMissingChunks(): array
    {
        return $this->missingChunks;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Not all chunks have been received',
            'missing_chunks' => $this->missingChunks
        ], 400);
    }
}
