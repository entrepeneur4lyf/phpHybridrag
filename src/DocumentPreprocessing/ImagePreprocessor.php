<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use OpenAI;

/**
 * Class ImagePreprocessor
 *
 * This class is responsible for preprocessing image documents.
 */
class ImagePreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private Configuration $config;
    private OpenAI\Client $openai;

    /**
     * ImagePreprocessor constructor.
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
     * Parse the image document and extract its content.
     *
     * @param string $filePath The path to the image file
     * @return string The extracted content from the image
     * @throws HybridRAGException If parsing fails
     */
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

    /**
     * Extract metadata from the image file.
     *
     * @param string $filePath The path to the image file
     * @return array The extracted metadata
     * @throws HybridRAGException If metadata extraction fails
     */
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
        // For images, we might not need to chunk the extracted text
        return [$text];
    }

    /**
     * Perform OCR on the image using GPT-4 Vision.
     *
     * @param string $filePath The path to the image file
     * @return string The extracted text from the image
     */
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
