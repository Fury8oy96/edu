<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'student_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Get the event this participation belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the student who is participating
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id');
    }
}
