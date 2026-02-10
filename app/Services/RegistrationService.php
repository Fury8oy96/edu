<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Students;
use App\Exceptions\EventNotFoundException;
use App\Exceptions\EventNotUpcomingException;
use App\Exceptions\StudentNotVerifiedException;
use App\Exceptions\AlreadyRegisteredException;
use App\Exceptions\EventFullException;
use App\Exceptions\NotRegisteredException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationService
{
    /**
     * Register a student for an upcoming event
     * 
     * @param int $eventId
     * @param int $studentId
     * @return EventRegistration
     * @throws EventNotFoundException
     * @throws EventNotUpcomingException
     * @throws StudentNotVerifiedException
     * @throws AlreadyRegisteredException
     * @throws EventFullException
     */
    public function register(int $eventId, int $studentId): EventRegistration
    {
        return DB::transaction(function () use ($eventId, $studentId) {
            // Lock event for update to prevent race conditions
            $event = Event::lockForUpdate()->find($eventId);

            if (!$event) {
                throw new EventNotFoundException($eventId);
            }

            // Check if event is in upcoming state (Requirement 4.3)
            if (!$event->isUpcoming()) {
                throw new EventNotUpcomingException();
            }

            // Get student and verify email verification (Requirement 4.2)
            $student = Students::findOrFail($studentId);
            if (!$student->isVerified()) {
                throw new StudentNotVerifiedException();
            }

            // Check if student is already registered (Requirement 4.4)
            $existingRegistration = EventRegistration::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();

            if ($existingRegistration) {
                throw new AlreadyRegisteredException();
            }

            // Check if event has capacity (Requirement 4.5)
            if (!$event->hasCapacity()) {
                throw new EventFullException();
            }

            // Create registration record (Requirement 4.1)
            $registration = EventRegistration::create([
                'event_id' => $eventId,
                'student_id' => $studentId,
                'registered_at' => now(),
            ]);

            // Increment registration count (Requirement 4.7)
            $event->increment('registration_count');

            Log::info("Student registered for event", [
                'event_id' => $eventId,
                'student_id' => $studentId,
                'registration_id' => $registration->id,
            ]);

            return $registration;
        });
    }

    /**
     * Unregister a student from an upcoming event
     * 
     * @param int $eventId
     * @param int $studentId
     * @return void
     * @throws EventNotFoundException
     * @throws EventNotUpcomingException
     * @throws NotRegisteredException
     */
    public function unregister(int $eventId, int $studentId): void
    {
        DB::transaction(function () use ($eventId, $studentId) {
            // Lock event for update
            $event = Event::lockForUpdate()->find($eventId);

            if (!$event) {
                throw new EventNotFoundException($eventId);
            }

            // Check if event is in upcoming state (Requirement 8.3)
            if (!$event->isUpcoming()) {
                throw new EventNotUpcomingException();
            }

            // Find registration record
            $registration = EventRegistration::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();

            // Check if student is registered (Requirement 8.2)
            if (!$registration) {
                throw new NotRegisteredException();
            }

            // Delete registration record (Requirement 8.1)
            $registration->delete();

            // Decrement registration count (Requirement 7.4, 8.5)
            $event->decrement('registration_count');

            Log::info("Student unregistered from event", [
                'event_id' => $eventId,
                'student_id' => $studentId,
            ]);
        });
    }

    /**
     * Check if student is registered for an event
     * 
     * @param int $eventId
     * @param int $studentId
     * @return bool
     */
    public function isRegistered(int $eventId, int $studentId): bool
    {
        return EventRegistration::where('event_id', $eventId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Get all registrations for an event
     * 
     * @param int $eventId
     * @return Collection
     */
    public function getEventRegistrations(int $eventId): Collection
    {
        return EventRegistration::where('event_id', $eventId)
            ->with('student')
            ->orderBy('registered_at', 'desc')
            ->get();
    }
}
