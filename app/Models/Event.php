<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'state',
        'max_participants',
        'registration_count',
        'participation_count',
        'attendance_count',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get all registrations for this event
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get all participations for this event
     */
    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipation::class);
    }

    /**
     * Get all attendance records for this event
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /**
     * Scope a query to only include upcoming events
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('state', 'upcoming');
    }

    /**
     * Scope a query to only include ongoing events
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->where('state', 'ongoing');
    }

    /**
     * Scope a query to only include past events
     */
    public function scopePast(Builder $query): void
    {
        $query->where('state', 'past');
    }

    /**
     * Check if event has capacity for more participants
     */
    public function hasCapacity(): bool
    {
        if ($this->max_participants === null) {
            return true;
        }
        return $this->registration_count < $this->max_participants;
    }

    /**
     * Check if event is in upcoming state
     */
    public function isUpcoming(): bool
    {
        return $this->state === 'upcoming';
    }

    /**
     * Check if event is in ongoing state
     */
    public function isOngoing(): bool
    {
        return $this->state === 'ongoing';
    }

    /**
     * Check if event is in past state
     */
    public function isPast(): bool
    {
        return $this->state === 'past';
    }

    /**
     * Check if event should transition to ongoing
     */
    public function shouldTransitionToOngoing(): bool
    {
        return $this->state === 'upcoming' && now()->gte($this->start_time);
    }

    /**
     * Check if event should transition to past
     */
    public function shouldTransitionToPast(): bool
    {
        return $this->state === 'ongoing' && now()->gte($this->end_time);
    }
}
