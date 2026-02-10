<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventParticipation;
use App\Models\EventAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StateTransitionService
{
    /**
     * Process all pending state transitions
     * Called by Laravel scheduler every minute
     * 
     * @return array Summary of transitions performed
     */
    public function processTransitions(): array
    {
        $transitionedToOngoing = 0;
        $transitionedToPast = 0;

        try {
            // Find events that should transition from upcoming to ongoing (Requirement 2.1)
            $upcomingEvents = Event::where('state', 'upcoming')
                ->where('start_time', '<=', now())
                ->get();

            foreach ($upcomingEvents as $event) {
                try {
                    $this->transitionToOngoing($event);
                    $transitionedToOngoing++;
                } catch (\Exception $e) {
                    Log::error("Failed to transition event to ongoing", [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Find events that should transition from ongoing to past (Requirement 2.2)
            $ongoingEvents = Event::where('state', 'ongoing')
                ->where('end_time', '<=', now())
                ->get();

            foreach ($ongoingEvents as $event) {
                try {
                    $this->transitionToPast($event);
                    $transitionedToPast++;
                } catch (\Exception $e) {
                    Log::error("Failed to transition event to past", [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("State transitions processed", [
                'transitioned_to_ongoing' => $transitionedToOngoing,
                'transitioned_to_past' => $transitionedToPast,
            ]);

        } catch (\Exception $e) {
            Log::error("State transition process failed", [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'transitioned_to_ongoing' => $transitionedToOngoing,
            'transitioned_to_past' => $transitionedToPast,
        ];
    }

    /**
     * Transition event from upcoming to ongoing
     * 
     * @param Event $event
     * @return void
     */
    public function transitionToOngoing(Event $event): void
    {
        DB::transaction(function () use ($event) {
            // Update event state (Requirement 2.1)
            $event->update(['state' => 'ongoing']);

            // Convert registrations to participations (Requirement 2.4)
            $this->convertRegistrationsToParticipations($event);

            Log::info("Event transitioned to ongoing", [
                'event_id' => $event->id,
                'title' => $event->title,
            ]);
        });
    }

    /**
     * Transition event from ongoing to past
     * 
     * @param Event $event
     * @return void
     */
    public function transitionToPast(Event $event): void
    {
        DB::transaction(function () use ($event) {
            // Update event state (Requirement 2.2)
            $event->update(['state' => 'past']);

            // Convert participations to attendance records (Requirement 2.5)
            $this->convertParticipationsToAttendance($event);

            Log::info("Event transitioned to past", [
                'event_id' => $event->id,
                'title' => $event->title,
            ]);
        });
    }

    /**
     * Convert registrations to participations
     * 
     * @param Event $event
     * @return void
     */
    protected function convertRegistrationsToParticipations(Event $event): void
    {
        // Get all registrations for the event
        $registrations = EventRegistration::where('event_id', $event->id)->get();

        $transitionTime = now();
        $participationCount = 0;

        foreach ($registrations as $registration) {
            // Create participation record with joined_at = transition time (Requirement 5.1)
            EventParticipation::create([
                'event_id' => $event->id,
                'student_id' => $registration->student_id,
                'joined_at' => $transitionTime,
            ]);

            $participationCount++;
        }

        // Delete all registration records
        EventRegistration::where('event_id', $event->id)->delete();

        // Update participation count
        $event->update([
            'participation_count' => $participationCount,
            'registration_count' => 0,
        ]);

        Log::info("Converted registrations to participations", [
            'event_id' => $event->id,
            'count' => $participationCount,
        ]);
    }

    /**
     * Convert participations to attendance records
     * 
     * @param Event $event
     * @return void
     */
    protected function convertParticipationsToAttendance(Event $event): void
    {
        // Get all participations for the event
        $participations = EventParticipation::where('event_id', $event->id)->get();

        $attendanceCount = 0;

        foreach ($participations as $participation) {
            // Calculate duration in minutes (Requirement 6.5)
            $durationMinutes = $participation->joined_at->diffInMinutes($event->end_time);

            // Create attendance record (Requirement 6.1, 6.2)
            EventAttendance::create([
                'event_id' => $event->id,
                'student_id' => $participation->student_id,
                'participation_start' => $participation->joined_at,
                'event_end' => $event->end_time,
                'duration_minutes' => $durationMinutes,
            ]);

            $attendanceCount++;
        }

        // Delete all participation records
        EventParticipation::where('event_id', $event->id)->delete();

        // Update attendance count
        $event->update([
            'attendance_count' => $attendanceCount,
            'participation_count' => 0,
        ]);

        Log::info("Converted participations to attendance", [
            'event_id' => $event->id,
            'count' => $attendanceCount,
        ]);
    }
}
