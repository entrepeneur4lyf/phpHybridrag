<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use OpenAI;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

/**
 * Class AudioPreprocessor
 *
 * This class is responsible for preprocessing audio documents.
 */
class AudioPreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private OpenAI\Client $openai;
    private Configuration $config;
    /**
     * AudioPreprocessor constructor.
     *
     * @param Logger $logger The logger instance
     * @param Configuration $config The configuration instance
     */
    public function __construct(Logger $logger, Configuration $config)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->openai = OpenAI::client($this->config->openai['api_key']);
    }

    /**
     * Parse the audio document and convert it to text.
     *
     * @param string $filePath The path to the audio file
     * @return string The parsed text from the audio
     * @throws HybridRAGException If parsing fails
     */
    public function parseDocument(string $filePath): array
    {
        try {
            $this->logger->info("Parsing audio", ['filePath' => $filePath]);
            
            // Convert audio to mp3 if it's not already
            $mp3Path = $this->convertToMp3($filePath);
            
            // Perform speech-to-text using Whisper
            $result = $this->performSpeechToText($mp3Path);
            
            // Extract the transcribed text and segments
            $text = $result['text'] ?? '';
            $segments = $result['segments'] ?? [];
            $words = $result['words'] ?? [];
            
            // Prepare structured output for graphing and vectorization
            $structuredOutput = [
                'text' => $text,
                'segments' => array_map(function($segment) {
                    return [
                        'start' => $segment['start'],
                        'end' => $segment['end'],
                        'text' => $segment['text'],
                    ];
                }, $segments),
                'words' => array_map(function($word) {
                    return [
                        'word' => $word['word'],
                        'start' => $word['start'],
                        'end' => $word['end'],
                    ];
                }, $words),
            ];
            
            $this->logger->info("Audio parsed successfully", ['filePath' => $filePath]);
            return $structuredOutput; // Return structured output
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse audio", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to parse audio: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract metadata from the audio file.
     *
     * @param string $filePath The path to the audio file
     * @return array The extracted metadata
     * @throws HybridRAGException If metadata extraction fails
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            $this->logger->info("Extracting metadata from audio", ['filePath' => $filePath]);
            
            $ffprobe = FFProbe::create();
            $metadata = $ffprobe->format($filePath)->all();
            
            $extractedMetadata = [
                'duration' => $metadata['duration'] ?? null,
                'bit_rate' => $metadata['bit_rate'] ?? null,
                'channels' => $metadata['channels'] ?? null,
                'sample_rate' => $metadata['sample_rate'] ?? null,
            ];
            
            $this->logger->info("Metadata extracted successfully from audio", ['filePath' => $filePath]);
            return $extractedMetadata;
        } catch (\Exception $e) {
            $this->logger->error("Failed to extract metadata from audio", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to extract metadata from audio: " . $e->getMessage(), 0, $e);
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
     * Convert the audio file to MP3 format.
     *
     * @param string $filePath The path to the audio file
     * @return string The path to the converted MP3 file
     */
    private function convertToMp3(string $filePath): string
    {
        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($filePath);

        $format = new \FFMpeg\Format\Audio\Mp3();
        $mp3Path = sys_get_temp_dir() . '/' . uniqid() . '.mp3';
        $audio->save($format, $mp3Path);

        return $mp3Path;
    }

    /**
     * Perform speech-to-text conversion using OpenAI's Whisper model.
     *
     * @param string $audioPath The path to the audio file
     * @return array The transcribed text and timestam
     */
    private function performSpeechToText(string $audioPath): array
    {
        $response = $this->openai->audio()->transcribe([
            'model' => 'whisper-1',
            'temperature' => 0.2,
            'file' => fopen($audioPath, 'r'),
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['segment', 'word']
        ]);
        
        return json_decode($response, true); // Decode the response to an associative array
    }
}
