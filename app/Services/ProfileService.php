<?php

namespace App\Services;

use App\Models\Students;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Exceptions\FileStorageException;

class ProfileService
{
    /**
     * Get complete profile with statistics
     * 
     * @param Students $student
     * @return array
     */
    public function getProfile(Students $student): array
    {
        // Calculate learning statistics
        $totalEnrolled = $student->courses()->count();
        $completedCourses = $student->courses()
            ->wherePivot('progress_percentage', 100)
            ->count();
        
        // For now, certificates count equals completed courses
        // This can be adjusted if there's a separate certificates table
        $certificatesEarned = $completedCourses;
        
        $statistics = [
            'total_enrolled_courses' => $totalEnrolled,
            'completed_courses' => $completedCourses,
            'certificates_earned' => $certificatesEarned,
        ];
        
        // Add statistics to student object for resource transformation
        $student->statistics = $statistics;
        
        return [
            'student' => $student,
            'statistics' => $statistics,
        ];
    }

    /**
     * Update profile information
     *
     * @param Students $student
     * @param array $data
     * @return Students
     */
    public function updateProfile(Students $student, array $data): Students
    {
        DB::beginTransaction();
        try {
            // Update only provided fields
            if (isset($data['name'])) {
                $student->name = $data['name'];
            }

            if (array_key_exists('profession', $data)) {
                $student->profession = $data['profession'];
            }

            $student->save();

            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload and store avatar
     * 
     * @param Students $student
     * @param UploadedFile $file
     * @return Students
     * @throws FileStorageException
     */
    public function uploadAvatar(Students $student, UploadedFile $file): Students
    {
        DB::beginTransaction();
        try {
            // Delete old avatar if exists
            if ($student->avatar) {
                $this->deleteAvatarFile($student->avatar);
            }
            
            // Store new avatar
            $path = $file->store('avatars', 'public');
            
            if (!$path) {
                throw new FileStorageException('Failed to store avatar file');
            }
            
            // Update student record
            $student->avatar = $path;
            $student->save();
            
            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Avatar upload failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            throw new FileStorageException('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    /**
     * Remove avatar
     * 
     * @param Students $student
     * @return Students
     * @throws \Exception
     */
    public function removeAvatar(Students $student): Students
    {
        if (!$student->avatar) {
            throw new \Exception('No avatar to remove');
        }
        
        DB::beginTransaction();
        try {
            // Delete avatar file from storage
            $this->deleteAvatarFile($student->avatar);
            
            // Set student avatar field to null
            $student->avatar = null;
            $student->save();
            
            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Avatar removal failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get learning progress
     * 
     * @param Students $student
     * @return Collection
     */
    public function getLearningProgress(Students $student): Collection
    {
        return $student->courses()
            ->orderByPivot('enrolled_at', 'desc')
            ->get();
    }

    /**
     * Get learning statistics
     * 
     * @param Students $student
     * @return array
     */
    public function getStatistics(Students $student): array
    {
        $totalEnrolled = $student->courses()->count();
        $completedCourses = $student->courses()
            ->wherePivot('progress_percentage', 100)
            ->count();
        
        // For now, certificates count equals completed courses
        $certificatesEarned = $completedCourses;
        
        return [
            'total_enrolled_courses' => $totalEnrolled,
            'completed_courses' => $completedCourses,
            'certificates_earned' => $certificatesEarned,
        ];
    }

    /**
     * Get subscription status
     * 
     * @param Students $student
     * @return array|null
     */
    public function getSubscriptionStatus(Students $student): ?array
    {
        $subscription = $student->getActiveSubscription();
        
        if (!$subscription) {
            return null;
        }
        
        return [
            'plan_name' => $subscription->subscriptionPlan->name,
            'subscription_expires_at' => $subscription->subscription_expires_at,
        ];
    }

    /**
     * Get payment history
     * 
     * @param Students $student
     * @return Collection
     */
    public function getPaymentHistory(Students $student): Collection
    {
        return $student->payments()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete avatar file from storage
     * 
     * @param string|null $avatarPath
     * @return void
     */
    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (!$avatarPath) {
            return;
        }
        
        try {
            if (Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete avatar file', [
                'path' => $avatarPath,
                'error' => $e->getMessage()
            ]);
        }
    }
}
