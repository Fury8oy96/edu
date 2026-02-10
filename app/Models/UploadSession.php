<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'filename',
        'file_size',
        'total_chunks',
        'received_chunks',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_chunks' => 'array',
            'file_size' => 'integer',
            'total_chunks' => 'integer',
        ];
    }

    /**
     * Check if the upload session has expired.
     * Sessions expire after 24 hours.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->created_at->addHours(24)->isPast();
    }

    /**
     * Check if all chunks have been received.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return count($this->received_chunks) === $this->total_chunks;
    }

    /**
     * Mark a chunk as received.
     * Adds the chunk number to the received_chunks array if not already present.
     *
     * @param int $chunkNumber The chunk sequence number (0-indexed)
     * @return void
     */
    public function markChunkReceived(int $chunkNumber): void
    {
        $chunks = $this->received_chunks;
        if (!in_array($chunkNumber, $chunks)) {
            $chunks[] = $chunkNumber;
            sort($chunks);
            $this->received_chunks = $chunks;
            $this->save();
        }
    }
}
