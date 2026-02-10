<?php

use App\Exceptions\FFMPEGException;
use App\Services\FFMPEGService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->ffmpegService = new FFMPEGService();
});

describe('getQualityConfig', function () {
    test('returns correct configuration for 360p', function () {
        $config = $this->ffmpegService->getQualityConfig('360p');
        
        expect($config)->toBe([
            'width' => 640,
            'height' => 360,
            'bitrate' => '800k',
        ]);
    });

    test('returns correct configuration for 480p', function () {
        $config = $this->ffmpegService->getQualityConfig('480p');
        
        expect($config)->toBe([
            'width' => 854,
            'height' => 480,
            'bitrate' => '1400k',
        ]);
    });

    test('returns correct configuration for 720p', function () {
        $config = $this->ffmpegService->getQualityConfig('720p');
        
        expect($config)->toBe([
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2800k',
        ]);
    });

    test('returns correct configuration for 1080p', function () {
        $config = $this->ffmpegService->getQualityConfig('1080p');
        
        expect($config)->toBe([
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
        ]);
    });

    test('throws exception for invalid quality level', function () {
        $this->ffmpegService->getQualityConfig('4k');
    })->throws(FFMPEGException::class, 'Invalid quality level: 4k');

    test('throws exception for empty quality level', function () {
        $this->ffmpegService->getQualityConfig('');
    })->throws(FFMPEGException::class);
});

describe('extractMetadata', function () {
    test('throws exception when video file does not exist', function () {
        $this->ffmpegService->extractMetadata('/path/to/nonexistent/video.mp4');
    })->throws(FFMPEGException::class, 'Video file not found');

    test('successfully extracts metadata from valid video', function () {
        // Create a temporary file to simulate video existence
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        // Mock ffprobe response
        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'codec_name' => 'h264',
                    'width' => 1920,
                    'height' => 1080,
                ]
            ],
            'format' => [
                'duration' => '120.5',
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        $metadata = $this->ffmpegService->extractMetadata($tempFile);

        expect($metadata)->toBeArray();
        expect($metadata)->toHaveKeys(['duration', 'resolution', 'codec', 'format']);
        expect($metadata['duration'])->toBe(120.5);
        expect($metadata['resolution'])->toBe('1920x1080');
        expect($metadata['codec'])->toBe('h264');
        expect($metadata['format'])->toBe('mp4');

        // Cleanup
        unlink($tempFile);
    });

    test('handles video with missing duration', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'codec_name' => 'h264',
                    'width' => 1280,
                    'height' => 720,
                ]
            ],
            'format' => [
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        $metadata = $this->ffmpegService->extractMetadata($tempFile);

        expect($metadata['duration'])->toBe(0.0);

        unlink($tempFile);
    });

    test('throws exception when ffprobe fails', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        Process::fake([
            '*ffprobe*' => Process::result(
                output: '',
                errorOutput: 'Invalid data found when processing input',
                exitCode: 1
            ),
        ]);

        try {
            $this->ffmpegService->extractMetadata($tempFile);
        } finally {
            unlink($tempFile);
        }
    })->throws(FFMPEGException::class, 'Failed to extract metadata from video');

    test('throws exception when no video stream found', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'audio',
                    'codec_name' => 'aac',
                ]
            ],
            'format' => [
                'duration' => '120.5',
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        try {
            $this->ffmpegService->extractMetadata($tempFile);
        } finally {
            unlink($tempFile);
        }
    })->throws(FFMPEGException::class, 'No video stream found in file');

    test('handles video with unknown codec and format', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'width' => 640,
                    'height' => 480,
                ]
            ],
            'format' => [
                'duration' => '60.0',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        $metadata = $this->ffmpegService->extractMetadata($tempFile);

        expect($metadata['codec'])->toBe('unknown');
        expect($metadata['format'])->toBe('unknown');

        unlink($tempFile);
    });
});

describe('transcodeVideo', function () {
    test('throws exception when input file does not exist', function () {
        $this->ffmpegService->transcodeVideo(
            '/path/to/nonexistent/input.mp4',
            '/path/to/output.mp4',
            '720p'
        );
    })->throws(FFMPEGException::class, 'Input video file not found');

    test('throws exception for invalid quality level', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($tempFile, 'fake video content');

        try {
            $this->ffmpegService->transcodeVideo(
                $tempFile,
                '/path/to/output.mp4',
                '4k'
            );
        } finally {
            unlink($tempFile);
        }
    })->throws(FFMPEGException::class, 'Invalid quality level: 4k');

    test('successfully transcodes video', function () {
        $inputFile = tempnam(sys_get_temp_dir(), 'input_');
        $outputFile = tempnam(sys_get_temp_dir(), 'output_');
        file_put_contents($inputFile, 'fake input video');

        Process::fake([
            '*ffmpeg*' => Process::result(
                output: 'Transcoding successful',
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        // Create output file to simulate successful transcoding
        file_put_contents($outputFile, 'fake transcoded video');

        $result = $this->ffmpegService->transcodeVideo(
            $inputFile,
            $outputFile,
            '720p'
        );

        expect($result)->toBeTrue();

        // Cleanup
        unlink($inputFile);
        unlink($outputFile);
    });

    test('throws exception when ffmpeg fails', function () {
        $inputFile = tempnam(sys_get_temp_dir(), 'input_');
        file_put_contents($inputFile, 'fake input video');

        Process::fake([
            '*ffmpeg*' => Process::result(
                output: '',
                errorOutput: 'Error while decoding stream',
                exitCode: 1
            ),
        ]);

        try {
            $this->ffmpegService->transcodeVideo(
                $inputFile,
                '/tmp/output.mp4',
                '720p'
            );
        } finally {
            unlink($inputFile);
        }
    })->throws(FFMPEGException::class, 'Failed to transcode video to 720p');
});

describe('generateThumbnail', function () {
    test('throws exception when video file does not exist', function () {
        $this->ffmpegService->generateThumbnail(
            '/path/to/nonexistent/video.mp4',
            '/path/to/thumbnail.jpg'
        );
    })->throws(FFMPEGException::class, 'Video file not found');

    test('successfully generates thumbnail at 5 second mark', function () {
        $videoFile = tempnam(sys_get_temp_dir(), 'video_');
        $thumbnailFile = tempnam(sys_get_temp_dir(), 'thumb_');
        file_put_contents($videoFile, 'fake video content');

        // Mock metadata extraction
        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'codec_name' => 'h264',
                    'width' => 1920,
                    'height' => 1080,
                ]
            ],
            'format' => [
                'duration' => '120.0',
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
            '*ffmpeg*' => Process::result(
                output: 'Thumbnail generated',
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        // Create thumbnail file to simulate successful generation
        file_put_contents($thumbnailFile, 'fake thumbnail');

        $result = $this->ffmpegService->generateThumbnail(
            $videoFile,
            $thumbnailFile,
            5
        );

        expect($result)->toBeTrue();

        // Cleanup
        unlink($videoFile);
        unlink($thumbnailFile);
    });

    test('uses 1 second for short videos', function () {
        $videoFile = tempnam(sys_get_temp_dir(), 'video_');
        $thumbnailFile = tempnam(sys_get_temp_dir(), 'thumb_');
        file_put_contents($videoFile, 'fake video content');

        // Mock metadata extraction for short video (3 seconds)
        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'codec_name' => 'h264',
                    'width' => 1920,
                    'height' => 1080,
                ]
            ],
            'format' => [
                'duration' => '3.0',
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
            '*ffmpeg*' => Process::result(
                output: 'Thumbnail generated',
                errorOutput: '',
                exitCode: 0
            ),
        ]);

        // Create thumbnail file
        file_put_contents($thumbnailFile, 'fake thumbnail');

        $result = $this->ffmpegService->generateThumbnail(
            $videoFile,
            $thumbnailFile,
            5 // Request 5 seconds, but video is only 3 seconds
        );

        expect($result)->toBeTrue();

        // Cleanup
        unlink($videoFile);
        unlink($thumbnailFile);
    });

    test('throws exception when ffmpeg fails', function () {
        $videoFile = tempnam(sys_get_temp_dir(), 'video_');
        file_put_contents($videoFile, 'fake video content');

        // Mock metadata extraction
        $ffprobeOutput = json_encode([
            'streams' => [
                [
                    'codec_type' => 'video',
                    'codec_name' => 'h264',
                    'width' => 1920,
                    'height' => 1080,
                ]
            ],
            'format' => [
                'duration' => '120.0',
                'format_name' => 'mp4',
            ]
        ]);

        Process::fake([
            '*ffprobe*' => Process::result(
                output: $ffprobeOutput,
                errorOutput: '',
                exitCode: 0
            ),
            '*ffmpeg*' => Process::result(
                output: '',
                errorOutput: 'Error extracting frame',
                exitCode: 1
            ),
        ]);

        try {
            $this->ffmpegService->generateThumbnail(
                $videoFile,
                '/tmp/thumbnail.jpg',
                5
            );
        } finally {
            unlink($videoFile);
        }
    })->throws(FFMPEGException::class, 'Failed to generate thumbnail');
});
