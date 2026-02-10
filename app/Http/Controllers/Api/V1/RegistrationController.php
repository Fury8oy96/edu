<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationResource;
use App\Services\RegistrationService;
use App\Exceptions\EventNotFoundException;
use App\Exceptions\EventNotUpcomingException;
use App\Exceptions\StudentNotVerifiedException;
use App\Exceptions\AlreadyRegisteredException;
use App\Exceptions\EventFullException;
use App\Exceptions\NotRegisteredException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private RegistrationService $registrationService
    ) {}

    /**
     * Register for an upcoming event
     * POST /api/v1/events/{id}/register
     * 
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function register(Request $request, int $eventId): JsonResponse
    {
        try {
            $student = $request->user();
            
            $registration = $this->registrationService->register($eventId, $student->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully registered for event',
                'data' => new RegistrationResource($registration),
            ], 201);
        } catch (EventNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (EventNotUpcomingException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event is not in upcoming state',
            ], 400);
        } catch (StudentNotVerifiedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification required to register for events',
            ], 403);
        } catch (AlreadyRegisteredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are already registered for this event',
            ], 409);
        } catch (EventFullException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event has reached maximum capacity',
            ], 409);
        }
    }

    /**
     * Unregister from an upcoming event
     * DELETE /api/v1/events/{id}/unregister
     * 
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function unregister(Request $request, int $eventId): JsonResponse
    {
        try {
            $student = $request->user();
            
            $this->registrationService->unregister($eventId, $student->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully unregistered from event',
            ], 200);
        } catch (EventNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (EventNotUpcomingException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unregister from ongoing or past events',
            ], 400);
        } catch (NotRegisteredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not registered for this event',
            ], 404);
        }
    }
}
