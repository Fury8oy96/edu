<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventParticipation;
use App\Models\EventAttendance;
use App\Exceptions\EventNotFoundException;
use App\Exceptions\EventHasAttendanceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EventService
{
    /**
     * Create a new event
     * 
     * @param array $data
     * @return Event
     * @throws ValidationException
     */
    public function createEvent(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            // Validate time range (Requirement 1.3)
            if (isset($data['start_time']) && isset($data['end_time'])) {
                $startTime = \Carbon\Carbon::parse($data['start_time']);
                $endTime = \Carbon\Carbon::parse($data['end_time']);
                
                if ($endTime->lte($startTime)) {
                    throw ValidationException::withMessages([
                        'end_time' => ['The end time must be after the start time.']
                    ]);
                }
            }

            // Set initial state based on start_time (Requirement 1.2)
            // Always set to upcoming for new events, state transitions will handle the rest
            $data['state'] = 'upcoming';

            // Initialize counters
            $data['registration_count'] = 0;
            $data['participation_count'] = 0;
            $data['attendance_count'] = 0;

            // Create event (Requirement 1.1)
            $event = Event::create($data);

            Log::info("Event created successfully", [
                'event_id' => $event->id,
                'title' => $event->title,
                'state' => $event->state,
            ]);

            return $event;
        });
    }

    /**
     * Update an existing event
     * 
     * @param int $eventId
     * @param array $data
     * @return Event
     * @throws EventNotFoundException
     * @throws ValidationException
     */
    public function updateEvent(int $eventId, array $data): Event
    {
        return DB::transaction(function () use ($eventId, $data) {
            $event = Event::lockForUpdate()->find($eventId);

            if (!$event) {
                throw new EventNotFoundException($eventId);
            }

            // Validate time range if both times are provided (Requirement 1.3)
            $startTime = isset($data['start_time']) 
                ? \Carbon\Carbon::parse($data['start_time']) 
                : $event->start_time;
            $endTime = isset($data['end_time']) 
                ? \Carbon\Carbon::parse($data['end_time']) 
                : $event->end_time;

            if ($endTime->lte($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => ['The end time must be after the start time.']
                ]);
            }

            // Remove state from update data if present (Requirement 1.4)
            // Manual state changes are not allowed
            unset($data['state']);

            // Don't allow manual modification of counters
            unset($data['registration_count']);
            unset($data['participation_count']);
            unset($data['attendance_count']);

            // Update event
            $event->update($data);

            Log::info("Event updated successfully", [
                'event_id' => $event->id,
                'title' => $event->title,
            ]);

            return $event->fresh();
        });
    }

    /**
     * Delete an event
     * 
     * @param int $eventId
     * @return void
     * @throws EventNotFoundException
     * @throws EventHasAttendanceException
     */
    public function deleteEvent(int $eventId): void
    {
        DB::transaction(function () use ($eventId) {
            $event = Event::lockForUpdate()->find($eventId);

            if (!$event) {
                throw new EventNotFoundException($eventId);
            }

            // Prevent deletion of events with attendance records (Requirement 1.6)
            if (($event->isOngoing() || $event->isPast()) && $event->attendance_count > 0) {
                throw new EventHasAttendanceException($eventId);
            }

            // Delete event (cascade will handle related records) (Requirement 1.5)
            $event->delete();

            Log::info("Event deleted successfully", [
                'event_id' => $eventId,
            ]);
        });
    }

    /**
     * Get event details
     * 
     * @param int $eventId
     * @return Event
     * @throws EventNotFoundException
     */
    public function getEvent(int $eventId): Event
    {
        $event = Event::find($eventId);

        if (!$event) {
            throw new EventNotFoundException($eventId);
        }

        return $event;
    }

    /**
     * Get upcoming events for a student (not registered)
     * 
     * @param int $studentId
     * @return Collection
     */
    public function getUpcomingForStudent(int $studentId): Collection
    {
        // Get events where state = upcoming and student is not registered (Requirement 3.1)
        return Event::upcoming()
            ->whereDoesntHave('registrations', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('start_time', 'asc') // Requirement 3.5
            ->get();
    }

    /**
     * Get ongoing events student is participating in
     * 
     * @param int $studentId
     * @return Collection
     */
    public function getOngoingForStudent(int $studentId): Collection
    {
        // Get events where state = ongoing and student has participation record (Requirement 3.2)
        return Event::ongoing()
            ->whereHas('participations', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('start_time', 'desc') // Requirement 3.6
            ->get();
    }

    /**
     * Get past events student attended
     * 
     * @param int $studentId
     * @return Collection
     */
    public function getPastForStudent(int $studentId): Collection
    {
        // Get events where state = past and student has attendance record (Requirement 3.3)
        return Event::past()
            ->whereHas('attendances', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('end_time', 'desc') // Requirement 3.7
            ->get();
    }

    /**
     * Get event details with student's status
     * 
     * @param int $eventId
     * @param int $studentId
     * @return array
     */
    public function getEventWithStudentStatus(int $eventId, int $studentId): array
    {
        $event = $this->getEvent($eventId);

        $studentStatus = [
            'is_registered' => false,
            'is_participating' => false,
            'has_attended' => false,
            'registration' => null,
            'participation' => null,
            'attendance' => null,
        ];

        // Check registration status for upcoming events (Requirement 10.3)
        if ($event->isUpcoming()) {
            $registration = EventRegistration::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();
            
            if ($registration) {
                $studentStatus['is_registered'] = true;
                $studentStatus['registration'] = $registration;
            }
        }

        // Check participation status for ongoing events (Requirement 10.4)
        if ($event->isOngoing()) {
            $participation = EventParticipation::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();
            
            if ($participation) {
                $studentStatus['is_participating'] = true;
                $studentStatus['participation'] = $participation;
            }
        }

        // Check attendance status for past events (Requirement 10.5)
        if ($event->isPast()) {
            $attendance = EventAttendance::where('event_id', $eventId)
                ->where('student_id', $studentId)
                ->first();
            
            if ($attendance) {
                $studentStatus['has_attended'] = true;
                $studentStatus['attendance'] = $attendance;
            }
        }

        return [
            'event' => $event,
            'student_status' => $studentStatus,
        ];
    }
}
