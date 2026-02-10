<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when storage operations fail.
 */
class StorageException extends Exception
{
    protected ?string $path;
    protected ?string $operation;

    public function __construct(string $message, ?string $path = null, ?string $operation = null, int $code = 0)
    {
        $this->path = $path;
        $this->operation = $operation;
        parent::__construct($message, $code);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage() ?: 'Storage operation failed',
        ], 500);
    }
}
