<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParticipationResource;
use App\Services\ParticipationService;
use App\Exceptions\EventNotFoundException;
use App\Exceptions\EventNotOngoingException;
use App\Exceptions\EventFullException;
use App\Exceptions\AlreadyParticipatingException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParticipationController extends Controller
{
    public function __construct(
        private ParticipationService $participationService
    ) {}

    /**
     * Join an ongoing event
     * POST /api/v1/events/{id}/join
     * 
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function join(Request $request, int $eventId): JsonResponse
    {
        try {
            $student = $request->user();
            
            $participation = $this->participationService->join($eventId, $student->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully joined event',
                'data' => new ParticipationResource($participation),
            ], 201);
        } catch (EventNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (EventNotOngoingException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event is not currently ongoing',
            ], 400);
        } catch (AlreadyParticipatingException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are already participating in this event',
            ], 409);
        } catch (EventFullException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event has reached maximum capacity',
            ], 409);
        }
    }
}
