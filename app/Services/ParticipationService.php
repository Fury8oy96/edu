<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipation;
use App\Exceptions\EventNotFoundException;
use App\Exceptions\EventNotOngoingException;
use App\Exceptions\EventFullException;
use App\Exceptions\AlreadyParticipatingException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParticipationService
{
    /**
     * Join an ongoing event
     * 
     * @param int $eventId
     * @param int $studentId
     * @return EventParticipation
     * @throws EventNotFoundException
     * @throws EventNotOngoingException
     * @throws EventFullException
     * @throws AlreadyParticipatingException
     */
    public function join(int $eventId, int $studentId): EventParticipation
    {
        return DB::transaction(function () use ($eventId, $studentId) {
            // Lock event for update to prevent race conditions
            $event = Event::lockForUpdate()->find($eventId);

            if (!$event) {
                throw new EventNotFoundException($eventId);
            }

            // Check if event is in ongoing state (Requirement 5.3)
            if (!$event->isOngoing()) {
                throw new EventNotOngoingException();
            }

            // Check if student is already participating
            $existingParticipation = EventParticipation::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();

            if ($existingParticipation) {
                throw new AlreadyParticipatingException();
            }

            // Check if event has capacity (Requirement 5.5)
            // For ongoing events, check against participation_count
            if ($event->max_participants !== null && 
                $event->participation_count >= $event->max_participants) {
                throw new EventFullException();
            }

            // Create participation record (Requirement 5.4)
            $participation = EventParticipation::create([
                'event_id' => $eventId,
                'student_id' => $studentId,
                'joined_at' => now(),
            ]);

            // Increment participation count
            $event->increment('participation_count');

            Log::info("Student joined event", [
                'event_id' => $eventId,
                'student_id' => $studentId,
                'participation_id' => $participation->id,
            ]);

            return $participation;
        });
    }

    /**
     * Check if student is participating in an event
     * 
     * @param int $eventId
     * @param int $studentId
     * @return bool
     */
    public function isParticipating(int $eventId, int $studentId): bool
    {
        return EventParticipation::where('event_id', $eventId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Get all participations for an event
     * 
     * @param int $eventId
     * @return Collection
     */
    public function getEventParticipations(int $eventId): Collection
    {
        return EventParticipation::where('event_id', $eventId)
            ->with('student')
            ->orderBy('joined_at', 'desc')
            ->get();
    }
}
