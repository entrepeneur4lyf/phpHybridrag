<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Video\VideoFilters;

/**
 * Class VideoPreprocessor
 *
 * This class is responsible for preprocessing video documents.
 */
class VideoPreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private AudioPreprocessor $audioPreprocessor;
    private ImagePreprocessor $imagePreprocessor;
    private FFMpeg $ffmpeg;

    /**
     * VideoPreprocessor constructor.
     *
     * @param Logger $logger The logger instance
     * @param AudioPreprocessor $audioPreprocessor The audio preprocessor instance
     * @param ImagePreprocessor $imagePreprocessor The image preprocessor instance
     */
    public function __construct(Logger $logger, AudioPreprocessor $audioPreprocessor, ImagePreprocessor $imagePreprocessor)
    {
        $this->logger = $logger;
        $this->audioPreprocessor = $audioPreprocessor;
        $this->imagePreprocessor = $imagePreprocessor;
        $this->ffmpeg = FFMpeg::create();
    }

    /**
     * Parse the video document and extract its content.
     *
     * @param string $filePath The path to the video file
     * @return string The extracted content from the video
     * @throws HybridRAGException If parsing fails
     */
    public function parseDocument(string $filePath): array
    {
        try {
            $this->logger->info("Parsing video", ['filePath' => $filePath]);
            
            $audioPath = $this->extractAudio($filePath);
            $audioResult = $this->audioPreprocessor->parseDocument($audioPath); // Get structured output
            
            $audioText = $audioResult['text']; // Extract the transcribed text
            $audioSegments = $audioResult['segments']; // Extract the segments
            $audioWords = $audioResult['words']; // Extract the words
            
            $keyFrames = $this->extractKeyFrames($filePath);
            $frameTexts = [];
            foreach ($keyFrames as $index => $frame) {
                $frameTexts[] = $this->imagePreprocessor->parseDocument($frame);
                unlink($frame); // Clean up temporary frame file
            }
            
            // Associate keyframes with audio segments
            $keyframeAssociations = [];
            foreach ($audioSegments as $segment) {
                $start = $segment['start'];
                $end = $segment['end'];
                $keyframeAssociations[] = [
                    'segment' => $segment,
                    'keyframes' => array_filter($keyFrames, function($keyFrame) use ($start, $end) {
                        // Use the timestamp from the keyframe
                        return $keyFrame['timestamp'] >= $start && $keyFrame['timestamp'] <= $end;
                    })
                ];
            }
            
            $text = $audioText . "\n" . implode("\n", $frameTexts);
            
            unlink($audioPath); // Clean up temporary audio file
            
            $this->logger->info("Video parsed successfully", ['filePath' => $filePath]);
            return [
                'text' => $text,
                'audio_segments' => $audioSegments, // Include audio segments in the return
                'audio_words' => $audioWords, // Include audio words in the return
                'keyframe_associations' => $keyframeAssociations // Include keyframe associations
            ];
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse video", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to parse video: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract metadata from the video file.
     *
     * @param string $filePath The path to the video file
     * @return array The extracted metadata
     * @throws HybridRAGException If metadata extraction fails
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            $this->logger->info("Extracting metadata from video", ['filePath' => $filePath]);
            
            $ffprobe = \FFMpeg\FFProbe::create();
            $metadata = $ffprobe->format($filePath)->all();
            
            $extractedMetadata = [
                'duration' => $metadata['duration'] ?? null,
                'bit_rate' => $metadata['bit_rate'] ?? null,
                'width' => $metadata['streams'][0]['width'] ?? null,
                'height' => $metadata['streams'][0]['height'] ?? null,
                'format' => $metadata['format_name'] ?? null,
                'codec' => $metadata['streams'][0]['codec_name'] ?? null,
            ];
            
            $this->logger->info("Metadata extracted successfully from video", ['filePath' => $filePath]);
            return $extractedMetadata;
        } catch (\Exception $e) {
            $this->logger->error("Failed to extract metadata from video", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to extract metadata from video: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Chunk the text into smaller segments.
     *
     * @param string $text The text to chunk
     * @param int $chunkSize The size of each chunk
     * @param int $overlap The overlap between chunks
     * @return array An array of text chunks
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        $words = explode(' ', $text);
        $chunks = [];
        $chunkWords = [];
        $wordCount = 0;

        foreach ($words as $word) {
            $chunkWords[] = $word;
            $wordCount++;

            if ($wordCount >= $chunkSize) {
                $chunks[] = implode(' ', $chunkWords);
                $chunkWords = array_slice($chunkWords, -$overlap);
                $wordCount = count($chunkWords);
            }
        }

        if (!empty($chunkWords)) {
            $chunks[] = implode(' ', $chunkWords);
        }

        return $chunks;
    }

    /**
     * Extract audio from the video file.
     *
     * @param string $videoPath The path to the video file
     * @return string The path to the extracted audio file
     */
    private function extractAudio(string $videoPath): string
    {
        $video = $this->ffmpeg->open($videoPath);
        $audioPath = sys_get_temp_dir() . '/' . uniqid() . '.mp3';
        
        $video->filters()->audioChannels(1)->audioFrequency(16000);
        $video->save(new \FFMpeg\Format\Audio\Mp3(), $audioPath);
        
        return $audioPath;
    }

    /**
     * Extract key frames from the video file.
     *
     * @param string $videoPath The path to the video file
     * @return array An array of paths to the extracted key frame images
     */
    private function extractKeyFrames(string $videoPath): array
    {
        $video = $this->ffmpeg->open($videoPath);
        $duration = $video->getStreams()->first()->get('duration');
        
        $frameCount = 5; // Extract 5 key frames
        $interval = $duration / ($frameCount + 1);
        
        $keyFrames = [];
        for ($i = 1; $i <= $frameCount; $i++) {
            $time = $interval * $i; // This is the timestamp for the keyframe
            $framePath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
            
            $video->filters()->synchronize();
            $video->frame(TimeCode::fromSeconds($time))->save($framePath);
            
            // Store both the frame path and its timestamp
            $keyFrames[] = [
                'path' => $framePath,
                'timestamp' => $time // Store the timestamp of the keyframe
            ];
        }
        
        return $keyFrames;
    }
}
