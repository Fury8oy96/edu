<?php

namespace App\Services;

use App\Exceptions\FFMPEGException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Service for handling FFMPEG operations including metadata extraction,
 * video transcoding, and thumbnail generation.
 */
class FFMPEGService
{
    /**
     * Quality configurations for different video quality levels.
     * Each quality level defines width, height, and bitrate.
     */
    private const QUALITY_CONFIGS = [
        '360p' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => '800k',
        ],
        '480p' => [
            'width' => 854,
            'height' => 480,
            'bitrate' => '1400k',
        ],
        '720p' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2800k',
        ],
        '1080p' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
        ],
    ];

    /**
     * Extract video metadata using FFMPEG probe.
     *
     * @param string $videoPath Path to video file
     * @return array ['duration' => float, 'resolution' => string, 'codec' => string, 'format' => string]
     * @throws FFMPEGException
     */
    public function extractMetadata(string $videoPath): array
    {
        if (!file_exists($videoPath)) {
            throw new FFMPEGException("Video file not found: {$videoPath}");
        }

        try {
            // Use ffprobe to extract metadata in JSON format
            $result = Process::run([
                'ffprobe',
                '-v', 'quiet',
                '-print_format', 'json',
                '-show_format',
                '-show_streams',
                $videoPath
            ]);

            if (!$result->successful()) {
                throw new FFMPEGException(
                    "Failed to extract metadata from video",
                    $result->errorOutput()
                );
            }

            $data = json_decode($result->output(), true);

            if (!$data || !isset($data['format']) || !isset($data['streams'])) {
                throw new FFMPEGException(
                    "Invalid metadata format returned from ffprobe",
                    $result->output()
                );
            }

            // Find the video stream
            $videoStream = null;
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            if (!$videoStream) {
                throw new FFMPEGException("No video stream found in file");
            }

            // Extract metadata
            $duration = isset($data['format']['duration']) 
                ? (float) $data['format']['duration'] 
                : 0.0;

            $width = $videoStream['width'] ?? 0;
            $height = $videoStream['height'] ?? 0;
            $resolution = "{$width}x{$height}";

            $codec = $videoStream['codec_name'] ?? 'unknown';
            $format = $data['format']['format_name'] ?? 'unknown';

            return [
                'duration' => $duration,
                'resolution' => $resolution,
                'codec' => $codec,
                'format' => $format,
            ];
        } catch (FFMPEGException $e) {
            // Re-throw FFMPEG exceptions
            throw $e;
        } catch (\Exception $e) {
            Log::error('FFMPEG metadata extraction failed', [
                'video_path' => $videoPath,
                'error' => $e->getMessage(),
            ]);

            throw new FFMPEGException(
                "Failed to extract metadata: {$e->getMessage()}",
                null,
                0,
                $e
            );
        }
    }

    /**
     * Get quality configuration for a specific quality level.
     *
     * @param string $quality Quality level (360p, 480p, 720p, 1080p)
     * @return array ['width' => int, 'height' => int, 'bitrate' => string]
     * @throws FFMPEGException
     */
    public function getQualityConfig(string $quality): array
    {
        if (!isset(self::QUALITY_CONFIGS[$quality])) {
            throw new FFMPEGException("Invalid quality level: {$quality}");
        }

        return self::QUALITY_CONFIGS[$quality];
    }

    /**
     * Transcode video to specific quality level.
     *
     * @param string $inputPath Input video path
     * @param string $outputPath Output video path
     * @param string $quality Quality level (360p, 480p, 720p, 1080p)
     * @param callable|null $progressCallback Optional progress callback
     * @return bool Success status
     * @throws FFMPEGException
     */
    public function transcodeVideo(
        string $inputPath,
        string $outputPath,
        string $quality,
        ?callable $progressCallback = null
    ): bool {
        if (!file_exists($inputPath)) {
            throw new FFMPEGException("Input video file not found: {$inputPath}");
        }

        $config = $this->getQualityConfig($quality);

        try {
            // Build FFMPEG command
            $command = [
                'ffmpeg',
                '-i', $inputPath,
                '-vf', "scale={$config['width']}:{$config['height']}",
                '-c:v', 'libx264',
                '-b:v', $config['bitrate'],
                '-c:a', 'aac',
                '-b:a', '128k',
                '-movflags', '+faststart',
                '-y', // Overwrite output file
                $outputPath
            ];

            $result = Process::run($command);

            if (!$result->successful()) {
                throw new FFMPEGException(
                    "Failed to transcode video to {$quality}",
                    $result->errorOutput()
                );
            }

            if (!file_exists($outputPath)) {
                throw new FFMPEGException("Transcoded video file was not created");
            }

            return true;
        } catch (FFMPEGException $e) {
            // Re-throw FFMPEG exceptions
            throw $e;
        } catch (\Exception $e) {
            Log::error('FFMPEG transcoding failed', [
                'input_path' => $inputPath,
                'output_path' => $outputPath,
                'quality' => $quality,
                'error' => $e->getMessage(),
            ]);

            throw new FFMPEGException(
                "Failed to transcode video: {$e->getMessage()}",
                null,
                0,
                $e
            );
        }
    }

    /**
     * Generate thumbnail from video at specified time.
     *
     * @param string $videoPath Path to video file
     * @param string $outputPath Output thumbnail path
     * @param int $timeInSeconds Time position for thumbnail (default: 5)
     * @return bool Success status
     * @throws FFMPEGException
     */
    public function generateThumbnail(
        string $videoPath,
        string $outputPath,
        int $timeInSeconds = 5
    ): bool {
        if (!file_exists($videoPath)) {
            throw new FFMPEGException("Video file not found: {$videoPath}");
        }

        try {
            // First, get video duration to validate time position
            $metadata = $this->extractMetadata($videoPath);
            $duration = $metadata['duration'];

            // If video is shorter than requested time, use 1 second
            if ($duration < $timeInSeconds) {
                $timeInSeconds = min(1, (int) $duration);
            }

            // Build FFMPEG command to extract thumbnail
            $command = [
                'ffmpeg',
                '-ss', (string) $timeInSeconds,
                '-i', $videoPath,
                '-vframes', '1',
                '-q:v', '2',
                '-y', // Overwrite output file
                $outputPath
            ];

            $result = Process::run($command);

            if (!$result->successful()) {
                throw new FFMPEGException(
                    "Failed to generate thumbnail",
                    $result->errorOutput()
                );
            }

            if (!file_exists($outputPath)) {
                throw new FFMPEGException("Thumbnail file was not created");
            }

            return true;
        } catch (FFMPEGException $e) {
            // Re-throw FFMPEG exceptions
            throw $e;
        } catch (\Exception $e) {
            Log::error('FFMPEG thumbnail generation failed', [
                'video_path' => $videoPath,
                'output_path' => $outputPath,
                'time' => $timeInSeconds,
                'error' => $e->getMessage(),
            ]);

            throw new FFMPEGException(
                "Failed to generate thumbnail: {$e->getMessage()}",
                null,
                0,
                $e
            );
        }
    }
}
