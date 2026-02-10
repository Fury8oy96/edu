<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when a chunk number is outside the valid range.
 */
class InvalidChunkException extends VideoException
{
    protected int $chunkNumber;
    protected int $maxChunks;

    public function __construct(int $chunkNumber, int $maxChunks)
    {
        $this->chunkNumber = $chunkNumber;
        $this->maxChunks = $maxChunks;
        
        $message = "Chunk number {$chunkNumber} is outside valid range (0-" . ($maxChunks - 1) . ")";
            
        parent::__construct($message, 400);
    }

    public function getChunkNumber(): int
    {
        return $this->chunkNumber;
    }

    public function getMaxChunks(): int
    {
        return $this->maxChunks;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Chunk number is outside valid range',
            'chunk_number' => $this->chunkNumber,
            'valid_range' => "0-" . ($this->maxChunks - 1)
        ], 400);
    }
}
