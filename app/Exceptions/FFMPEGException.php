<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when FFMPEG operations fail.
 */
class FFMPEGException extends Exception
{
    protected ?string $ffmpegOutput;

    public function __construct(string $message, ?string $ffmpegOutput = null, int $code = 0)
    {
        $this->ffmpegOutput = $ffmpegOutput;
        parent::__construct($message, $code);
    }

    public function getFfmpegOutput(): ?string
    {
        return $this->ffmpegOutput;
    }
}
