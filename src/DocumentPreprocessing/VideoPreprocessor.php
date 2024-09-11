<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Video\VideoFilters;

class VideoPreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private AudioPreprocessor $audioPreprocessor;
    private ImagePreprocessor $imagePreprocessor;
    private FFMpeg $ffmpeg;

    public function __construct(Logger $logger, AudioPreprocessor $audioPreprocessor, ImagePreprocessor $imagePreprocessor)
    {
        $this->logger = $logger;
        $this->audioPreprocessor = $audioPreprocessor;
        $this->imagePreprocessor = $imagePreprocessor;
        $this->ffmpeg = FFMpeg::create();
    }

    public function parseDocument(string $filePath): string
    {
        try {
            $this->logger->info("Parsing video", ['filePath' => $filePath]);
            
            $audioPath = $this->extractAudio($filePath);
            $audioText = $this->audioPreprocessor->parseDocument($audioPath);
            
            $keyFrames = $this->extractKeyFrames($filePath);
            $frameTexts = [];
            foreach ($keyFrames as $frame) {
                $frameTexts[] = $this->imagePreprocessor->parseDocument($frame);
                unlink($frame); // Clean up temporary frame file
            }
            
            $text = $audioText . "\n" . implode("\n", $frameTexts);
            
            unlink($audioPath); // Clean up temporary audio file
            
            $this->logger->info("Video parsed successfully", ['filePath' => $filePath]);
            return $text;
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse video", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to parse video: " . $e->getMessage(), 0, $e);
        }
    }

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

    private function extractAudio(string $videoPath): string
    {
        $video = $this->ffmpeg->open($videoPath);
        $audioPath = sys_get_temp_dir() . '/' . uniqid() . '.mp3';
        
        $video->filters()->audioChannels(1)->audioFrequency(16000);
        $video->save(new \FFMpeg\Format\Audio\Mp3(), $audioPath);
        
        return $audioPath;
    }

    private function extractKeyFrames(string $videoPath): array
    {
        $video = $this->ffmpeg->open($videoPath);
        $duration = $video->getStreams()->first()->get('duration');
        
        $frameCount = 5; // Extract 5 key frames
        $interval = $duration / ($frameCount + 1);
        
        $keyFrames = [];
        for ($i = 1; $i <= $frameCount; $i++) {
            $time = $interval * $i;
            $framePath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
            
            $video->filters()->synchronize();
            $video->frame(TimeCode::fromSeconds($time))->save($framePath);
            
            $keyFrames[] = $framePath;
        }
        
        return $keyFrames;
    }
}