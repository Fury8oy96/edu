<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventDetailResource;
use App\Services\EventService;
use App\Exceptions\EventNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private EventService $eventService
    ) {}

    /**
     * Get upcoming events (not registered)
     * GET /api/v1/events/upcoming
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $student = $request->user();
        
        $events = $this->eventService->getUpcomingForStudent($student->id);
        
        // Paginate results (Requirement 12.6)
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $paginatedEvents = $events->forPage($page, $perPage);
        $total = $events->count();
        
        return response()->json([
            'success' => true,
            'data' => EventResource::collection($paginatedEvents),
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get ongoing events (participating)
     * GET /api/v1/events/ongoing
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function ongoing(Request $request): JsonResponse
    {
        $student = $request->user();
        
        $events = $this->eventService->getOngoingForStudent($student->id);
        
        // Paginate results
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $paginatedEvents = $events->forPage($page, $perPage);
        $total = $events->count();
        
        return response()->json([
            'success' => true,
            'data' => EventResource::collection($paginatedEvents),
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get past events (attended)
     * GET /api/v1/events/past
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function past(Request $request): JsonResponse
    {
        $student = $request->user();
        
        $events = $this->eventService->getPastForStudent($student->id);
        
        // Paginate results
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $paginatedEvents = $events->forPage($page, $perPage);
        $total = $events->count();
        
        return response()->json([
            'success' => true,
            'data' => EventResource::collection($paginatedEvents),
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get event details with student status
     * GET /api/v1/events/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $student = $request->user();
            
            $result = $this->eventService->getEventWithStudentStatus($id, $student->id);
            
            // Add student_status to event resource
            $event = $result['event'];
            $event->student_status = $result['student_status'];
            
            return response()->json([
                'success' => true,
                'data' => new EventDetailResource($event),
            ]);
        } catch (EventNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }
    }
}
