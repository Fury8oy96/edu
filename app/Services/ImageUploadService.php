<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUploadService
{
    /**
     * Allowed image MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Maximum file size in bytes (5MB)
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes

    /**
     * Upload and store a featured image
     * 
     * @param UploadedFile $file
     * @param string $directory
     * @return string URL/path to stored image
     * @throws ValidationException
     */
    public function uploadImage(UploadedFile $file, string $directory = 'blog-images'): string
    {
        // Validate the image
        $this->validateImage($file);

        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);

        // Store the file in the public disk
        $path = $file->storeAs($directory, $filename, 'public');

        // Return the URL to the stored image
        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an image from storage
     * 
     * @param string $imagePath
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        // Extract the path from the URL if it's a full URL
        $path = $this->extractPathFromUrl($imagePath);

        // Check if file exists and delete it
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Validate image file
     * 
     * @param UploadedFile $file
     * @return bool
     * @throws ValidationException
     */
    private function validateImage(UploadedFile $file): bool
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'featured_image' => ['The uploaded file is not valid.'],
            ]);
        }

        // Check file size (≤5MB)
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'featured_image' => ['The featured image may not be greater than 5120 kilobytes.'],
            ]);
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                'featured_image' => ['The featured image must be a file of type: jpeg, png, gif, webp.'],
            ]);
        }

        return true;
    }

    /**
     * Generate a unique filename for the uploaded image
     * 
     * @param UploadedFile $file
     * @return string
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        // Generate UUID for uniqueness
        $uuid = Str::uuid();
        
        // Get the file extension
        $extension = $file->getClientOriginalExtension();
        
        // Return filename with UUID and extension
        return $uuid . '.' . $extension;
    }

    /**
     * Extract the storage path from a full URL
     * 
     * @param string $url
     * @return string
     */
    private function extractPathFromUrl(string $url): string
    {
        // If it's already a path (not a URL), return as is
        if (!str_contains($url, 'http://') && !str_contains($url, 'https://') && !str_contains($url, '/storage/')) {
            return $url;
        }

        // Extract the path after '/storage/'
        if (str_contains($url, '/storage/')) {
            $parts = explode('/storage/', $url);
            return isset($parts[1]) ? $parts[1] : $url;
        }
        
        return $url;
    }
}
