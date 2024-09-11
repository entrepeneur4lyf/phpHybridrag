<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;
use HybridRAG\Configuration;
/**
 * Class OpenAILanguageModel
 *
 * This class implements the LanguageModelInterface using the OpenAI API.
 */
class OpenAILanguageModel implements LanguageModelInterface
{
    private Configuration $config;
    private Client $client;

    /**
     * OpenAILanguageModel constructor.
     *
     * @param Configuration $config The configuration instance
     * @param Logger $logger The logger instance
     */
    public function __construct(
        Configuration $config,
        private Logger $logger
    ) {
        $this->client = new Client([
            'base_uri' => $this->config->openai['api_base_url'],
            'model' => $this->config->openai['language_model'],
            'temperature' => $this->config->openai['language_model']['temperature'],
            'max_tokens' => $this->config->openai['language_model']['max_tokens'],
            'top_p' => $this->config->openai['language_model']['top_p'],
            'frequency_penalty' => $this->config->openai['language_model']['frequency_penalty'],
            'presence_penalty' => $this->config->openai['language_model']['presence_penalty'],
            'headers' => [
                'Authorization' => "Bearer {$this->config->openai['api_key']}",
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate a response based on the given prompt and context.
     *
     * @param string $prompt The input prompt
     * @param array $context Additional context for the generation
     * @return string The generated response
     * @throws HybridRAGException If the API request fails
     */
    public function generateResponse(string $prompt, array $context): string
    {
        try {
            $this->logger->info("Generating response from OpenAI", ['model' => $this->config->openai['language_model']['model']]);
            $messages = $this->formatMessages($prompt, $context);

            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->config->openai['language_model']['model'],
                    'messages' => $messages,
                    'temperature' => $this->config->openai['language_model']['temperature'],
                    'max_tokens' => $this->config->openai['language_model']['max_tokens'],
                    'top_p' => $this->config->openai['language_model']['top_p'],
                    'frequency_penalty' => $this->config->openai['language_model']['frequency_penalty'],
                    'presence_penalty' => $this->config->openai['language_model']['presence_penalty'],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $generatedResponse = $result['choices'][0]['message']['content'] ?? '';
            $this->logger->info("Response generated successfully from OpenAI");
            return $generatedResponse;
        } catch (GuzzleException $e) {
            $this->logger->error("OpenAI API request failed", ['error' => $e->getMessage()]);
            throw new HybridRAGException("OpenAI API request failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Format the messages for the OpenAI API request.
     *
     * @param string $prompt The input prompt
     * @param array $context Additional context for the generation
     * @return array The formatted messages
     */
    private function formatMessages(string $prompt, array $context): array
    {
        return [
            ['role' => 'system', 'content' => 'You are an image description assistant. Analyze the provided image and describe its content in detail, including objects, people, actions, colors, and any relevant context.'],
            ['role' => 'user', 'content' => "Context:\n" . json_encode($context)],
            ['role' => 'user', 'content' => "Question: $prompt"]
        ];
    }
}
