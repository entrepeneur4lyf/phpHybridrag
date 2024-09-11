<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use OpenAI;

class ImagePreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private OpenAI\Client $openai;

    public function __construct(Logger $logger, string $openaiApiKey)
    {
        $this->logger = $logger;
        $this->openai = OpenAI::client($openaiApiKey);
    }

    public function parseDocument(string $filePath): string
    {
        try {
            $this->logger->info("Parsing image", ['filePath' => $filePath]);
            
            // Use GPT-4 Vision for OCR
            $text = $this->performOCR($filePath);
            
            $this->logger->info("Image parsed successfully", ['filePath' => $filePath]);
            return $text;
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse image", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to parse image: " . $e->getMessage(), 0, $e);
        }
    }

    public function extractMetadata(string $filePath): array
    {
        try {
            $this->logger->info("Extracting metadata from image", ['filePath' => $filePath]);
            
            $exif = @exif_read_data($filePath);
            
            $metadata = [
                'width' => $exif['COMPUTED']['Width'] ?? null,
                'height' => $exif['COMPUTED']['Height'] ?? null,
                'date_time' => $exif['DateTime'] ?? null,
                'camera' => $exif['Model'] ?? null,
            ];
            
            $this->logger->info("Metadata extracted successfully from image", ['filePath' => $filePath]);
            return $metadata;
        } catch (\Exception $e) {
            $this->logger->error("Failed to extract metadata from image", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to extract metadata from image: " . $e->getMessage(), 0, $e);
        }
    }

    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        // For images, we might not need to chunk the extracted text
        return [$text];
    }

    private function performOCR(string $filePath): string
    {
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Please extract and return all the text you can see in this image. Only return the extracted text, nothing else.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($filePath)),
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ]);

        return $response->choices[0]->message->content;
    }
}