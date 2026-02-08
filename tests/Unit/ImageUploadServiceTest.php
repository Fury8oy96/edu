<?php

use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->imageUploadService = new ImageUploadService();
    
    // Fake the storage disk
    Storage::fake('public');
});

test('uploadImage successfully uploads valid JPEG image', function () {
    // Create a fake JPEG image (1MB)
    $file = UploadedFile::fake()->image('test-image.jpg')->size(1024);
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL is returned
    expect($url)->toBeString();
    expect($url)->toContain('/storage/blog-images/');
    expect($url)->toContain('.jpg');
    
    // Extract filename from URL
    $filename = basename($url);
    
    // Assert file was stored in the correct directory
    Storage::disk('public')->assertExists('blog-images/' . $filename);
});

test('uploadImage successfully uploads valid PNG image', function () {
    // Create a fake PNG image
    $file = UploadedFile::fake()->image('test-image.png')->size(2048);
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL is returned
    expect($url)->toBeString();
    expect($url)->toContain('/storage/blog-images/');
    expect($url)->toContain('.png');
    
    // Extract filename from URL
    $filename = basename($url);
    
    // Assert file was stored
    Storage::disk('public')->assertExists('blog-images/' . $filename);
});

test('uploadImage successfully uploads valid GIF image', function () {
    // Create a fake GIF image
    $file = UploadedFile::fake()->image('test-image.gif')->size(512);
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL is returned
    expect($url)->toBeString();
    expect($url)->toContain('/storage/blog-images/');
    expect($url)->toContain('.gif');
});

test('uploadImage successfully uploads valid WebP image', function () {
    // Create a fake WebP image
    $file = UploadedFile::fake()->create('test-image.webp', 1024, 'image/webp');
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL is returned
    expect($url)->toBeString();
    expect($url)->toContain('/storage/blog-images/');
    expect($url)->toContain('.webp');
});

test('uploadImage generates unique filename', function () {
    // Create two fake images
    $file1 = UploadedFile::fake()->image('same-name.jpg')->size(1024);
    $file2 = UploadedFile::fake()->image('same-name.jpg')->size(1024);
    
    // Upload both images
    $url1 = $this->imageUploadService->uploadImage($file1);
    $url2 = $this->imageUploadService->uploadImage($file2);
    
    // Assert URLs are different (unique filenames)
    expect($url1)->not->toBe($url2);
    
    // Extract filenames
    $filename1 = basename($url1);
    $filename2 = basename($url2);
    
    // Assert both files exist
    Storage::disk('public')->assertExists('blog-images/' . $filename1);
    Storage::disk('public')->assertExists('blog-images/' . $filename2);
});

test('uploadImage stores in custom directory', function () {
    // Create a fake image
    $file = UploadedFile::fake()->image('test.jpg')->size(1024);
    
    // Upload to custom directory
    $url = $this->imageUploadService->uploadImage($file, 'custom-directory');
    
    // Assert URL contains custom directory
    expect($url)->toContain('/storage/custom-directory/');
    
    // Extract filename
    $filename = basename($url);
    
    // Assert file was stored in custom directory
    Storage::disk('public')->assertExists('custom-directory/' . $filename);
});

test('uploadImage rejects file larger than 5MB', function () {
    // Create a fake image larger than 5MB (5121KB)
    $file = UploadedFile::fake()->image('large-image.jpg')->size(5121);
    
    // Attempt to upload (should throw ValidationException)
    $this->imageUploadService->uploadImage($file);
})->throws(ValidationException::class, 'The featured image may not be greater than 5120 kilobytes.');

test('uploadImage accepts file exactly 5MB', function () {
    // Create a fake image exactly 5MB (5120KB)
    $file = UploadedFile::fake()->image('max-size.jpg')->size(5120);
    
    // Upload the image (should succeed)
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL is returned
    expect($url)->toBeString();
    expect($url)->toContain('/storage/blog-images/');
});

test('uploadImage rejects non-image file', function () {
    // Create a fake PDF file
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
    
    // Attempt to upload (should throw ValidationException)
    $this->imageUploadService->uploadImage($file);
})->throws(ValidationException::class, 'The featured image must be a file of type: jpeg, png, gif, webp.');

test('uploadImage rejects text file', function () {
    // Create a fake text file
    $file = UploadedFile::fake()->create('text.txt', 100, 'text/plain');
    
    // Attempt to upload (should throw ValidationException)
    $this->imageUploadService->uploadImage($file);
})->throws(ValidationException::class);

test('deleteImage successfully deletes existing image', function () {
    // Create and upload a fake image
    $file = UploadedFile::fake()->image('to-delete.jpg')->size(1024);
    $url = $this->imageUploadService->uploadImage($file);
    
    // Extract filename from URL
    $filename = basename($url);
    
    // Verify file exists
    Storage::disk('public')->assertExists('blog-images/' . $filename);
    
    // Delete the image
    $result = $this->imageUploadService->deleteImage($url);
    
    // Assert deletion was successful
    expect($result)->toBeTrue();
    
    // Assert file no longer exists
    Storage::disk('public')->assertMissing('blog-images/' . $filename);
});

test('deleteImage returns false for non-existent image', function () {
    // Try to delete a non-existent image
    $result = $this->imageUploadService->deleteImage('blog-images/non-existent.jpg');
    
    // Assert deletion returns false
    expect($result)->toBeFalse();
});

test('deleteImage handles full URL path', function () {
    // Create and upload a fake image
    $file = UploadedFile::fake()->image('url-test.jpg')->size(1024);
    $url = $this->imageUploadService->uploadImage($file);
    
    // Extract filename
    $filename = basename($url);
    
    // Verify file exists
    Storage::disk('public')->assertExists('blog-images/' . $filename);
    
    // Delete using full URL (as returned by uploadImage)
    $result = $this->imageUploadService->deleteImage($url);
    
    // Assert deletion was successful
    expect($result)->toBeTrue();
    
    // Assert file no longer exists
    Storage::disk('public')->assertMissing('blog-images/' . $filename);
});

test('deleteImage handles relative path', function () {
    // Create and upload a fake image
    $file = UploadedFile::fake()->image('path-test.jpg')->size(1024);
    $url = $this->imageUploadService->uploadImage($file);
    
    // Extract filename
    $filename = basename($url);
    $relativePath = 'blog-images/' . $filename;
    
    // Verify file exists
    Storage::disk('public')->assertExists($relativePath);
    
    // Delete using relative path
    $result = $this->imageUploadService->deleteImage($relativePath);
    
    // Assert deletion was successful
    expect($result)->toBeTrue();
    
    // Assert file no longer exists
    Storage::disk('public')->assertMissing($relativePath);
});

test('uploadImage preserves file extension', function () {
    // Test different extensions
    $extensions = ['jpg', 'png', 'gif'];
    
    foreach ($extensions as $ext) {
        $file = UploadedFile::fake()->image("test.$ext")->size(1024);
        $url = $this->imageUploadService->uploadImage($file);
        
        // Assert URL ends with the correct extension
        expect($url)->toEndWith(".$ext");
    }
});

test('uploadImage returns valid storage URL', function () {
    // Create a fake image
    $file = UploadedFile::fake()->image('url-format.jpg')->size(1024);
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Assert URL format is correct
    expect($url)->toBeString();
    expect($url)->toContain('/storage/');
    expect($url)->toContain('blog-images/');
    
    // Assert URL is accessible format (contains proper path structure)
    expect(strlen($url))->toBeGreaterThan(20);
});

test('uploadImage handles multiple uploads to same directory', function () {
    // Upload multiple images
    $urls = [];
    for ($i = 0; $i < 5; $i++) {
        $file = UploadedFile::fake()->image("image-$i.jpg")->size(1024);
        $urls[] = $this->imageUploadService->uploadImage($file);
    }
    
    // Assert all URLs are unique
    expect(count($urls))->toBe(count(array_unique($urls)));
    
    // Assert all files exist
    foreach ($urls as $url) {
        $filename = basename($url);
        Storage::disk('public')->assertExists('blog-images/' . $filename);
    }
});

test('uploadImage validates file before storing', function () {
    // Create an invalid file (too large)
    $file = UploadedFile::fake()->image('invalid.jpg')->size(6000);
    
    // Attempt to upload
    try {
        $this->imageUploadService->uploadImage($file);
        expect(false)->toBeTrue(); // Should not reach here
    } catch (ValidationException $e) {
        // Expected exception
    }
    
    // Assert no files were stored in blog-images directory
    $files = Storage::disk('public')->files('blog-images');
    expect($files)->toBeEmpty();
});

test('deleteImage handles empty string gracefully', function () {
    // Try to delete with empty string
    $result = $this->imageUploadService->deleteImage('');
    
    // Assert returns false
    expect($result)->toBeFalse();
});

test('uploadImage filename uses UUID format', function () {
    // Create a fake image
    $file = UploadedFile::fake()->image('test.jpg')->size(1024);
    
    // Upload the image
    $url = $this->imageUploadService->uploadImage($file);
    
    // Extract filename without extension
    $filename = basename($url, '.jpg');
    
    // Assert filename looks like a UUID (36 characters with hyphens)
    expect(strlen($filename))->toBe(36);
    expect($filename)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
