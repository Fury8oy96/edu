<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventAnalyticsService
{
    /**
     * Get event statistics
     * 
     * @param int $eventId
     * @return array
     */
    public function getEventStatistics(int $eventId): array
    {
        $event = Event::with(['registrations', 'participations', 'attendances'])
            ->findOrFail($eventId);

        // Calculate attendance rate (Requirement 9.3)
        $attendanceRate = $event->registration_count > 0
            ? ($event->attendance_count / $event->registration_count) * 100
            : 0;

        // Calculate average participation duration for this event (Requirement 9.4)
        $averageDuration = EventAttendance::where('event_id', $eventId)
            ->avg('duration_minutes') ?? 0;

        return [
            'event_id' => $event->id,
            'title' => $event->title,
            'state' => $event->state,
            'registration_count' => $event->registration_count,
            'participation_count' => $event->participation_count,
            'attendance_count' => $event->attendance_count,
            'attendance_rate' => round($attendanceRate, 2),
            'average_duration_minutes' => round($averageDuration, 2),
            'max_participants' => $event->max_participants,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
        ];
    }

    /**
     * Get overall event analytics
     * 
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getOverallAnalytics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Build base query
        $query = Event::query();

        // Apply date range filter if provided
        if ($startDate) {
            $query->where('start_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('start_time', '<=', $endDate);
        }

        $events = $query->get();

        // Total events by state (Requirement 9.1)
        $totalUpcoming = $events->where('state', 'upcoming')->count();
        $totalOngoing = $events->where('state', 'ongoing')->count();
        $totalPast = $events->where('state', 'past')->count();

        // Aggregate statistics (Requirement 9.5)
        $totalRegistrations = $events->sum('registration_count');
        $totalParticipations = $events->sum('participation_count');
        $totalAttendances = $events->sum('attendance_count');

        // Calculate overall attendance rate
        $overallAttendanceRate = $totalRegistrations > 0
            ? ($totalAttendances / $totalRegistrations) * 100
            : 0;

        // Calculate average participation duration across all past events
        $averageDuration = EventAttendance::query()
            ->when($startDate || $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereHas('event', function ($eventQuery) use ($startDate, $endDate) {
                    if ($startDate) {
                        $eventQuery->where('start_time', '>=', $startDate);
                    }
                    if ($endDate) {
                        $eventQuery->where('start_time', '<=', $endDate);
                    }
                });
            })
            ->avg('duration_minutes') ?? 0;

        return [
            'total_events' => $events->count(),
            'by_state' => [
                'upcoming' => $totalUpcoming,
                'ongoing' => $totalOngoing,
                'past' => $totalPast,
            ],
            'aggregate_statistics' => [
                'total_registrations' => $totalRegistrations,
                'total_participations' => $totalParticipations,
                'total_attendances' => $totalAttendances,
                'overall_attendance_rate' => round($overallAttendanceRate, 2),
                'average_duration_minutes' => round($averageDuration, 2),
            ],
            'date_range' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get attendance rate for an event
     * 
     * @param int $eventId
     * @return float
     */
    public function getAttendanceRate(int $eventId): float
    {
        $event = Event::findOrFail($eventId);

        if ($event->registration_count === 0) {
            return 0.0;
        }

        return round(($event->attendance_count / $event->registration_count) * 100, 2);
    }

    /**
     * Get average participation duration for an event
     * 
     * @param int $eventId
     * @return int
     */
    public function getAverageParticipationDuration(int $eventId): int
    {
        $averageDuration = EventAttendance::where('event_id', $eventId)
            ->avg('duration_minutes');

        return (int) round($averageDuration ?? 0);
    }
}
