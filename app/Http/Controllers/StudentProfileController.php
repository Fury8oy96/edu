<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use App\Http\Resources\StudentProfileResource;
use App\Http\Resources\LearningProgressResource;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Exceptions\FileStorageException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private ProfileService $profileService
    ) {}

    /**
     * Get authenticated student's profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $profileData = $this->profileService->getProfile($student);
            
            return response()->json(
                new StudentProfileResource($profileData['student']),
                200
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve profile',
            ], 500);
        }
    }

    /**
     * Update authenticated student's profile
     * 
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $student = $request->user();
            $updatedStudent = $this->profileService->updateProfile(
                $student,
                $request->validated()
            );
            
            return response()->json(
                new StudentProfileResource($updatedStudent),
                200
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * Upload avatar for authenticated student
     * 
     * @param UploadAvatarRequest $request
     * @return JsonResponse
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        try {
            $student = $request->user();
            $updatedStudent = $this->profileService->uploadAvatar(
                $student,
                $request->file('avatar')
            );
            
            return response()->json(
                new StudentProfileResource($updatedStudent),
                200
            );
        } catch (FileStorageException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload avatar',
            ], 500);
        }
    }

    /**
     * Remove avatar for authenticated student
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $updatedStudent = $this->profileService->removeAvatar($student);
            
            return response()->json(
                new StudentProfileResource($updatedStudent),
                200
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'No avatar to remove') {
                return response()->json([
                    'error' => 'No avatar to remove',
                ], 404);
            }
            
            return response()->json([
                'error' => 'Failed to remove avatar',
            ], 500);
        }
    }

    /**
     * Get learning progress for authenticated student
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function progress(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $courses = $this->profileService->getLearningProgress($student);
            
            return response()->json(
                LearningProgressResource::collection($courses),
                200
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve learning progress',
            ], 500);
        }
    }

    /**
     * Get learning statistics for authenticated student
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $statistics = $this->profileService->getStatistics($student);
            
            return response()->json($statistics, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
