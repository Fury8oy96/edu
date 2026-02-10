<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'student_id',
        'participation_start',
        'event_end',
        'duration_minutes',
    ];

    protected $casts = [
        'participation_start' => 'datetime',
        'event_end' => 'datetime',
    ];

    /**
     * Get the event this attendance record belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the student who attended
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id');
    }
}
