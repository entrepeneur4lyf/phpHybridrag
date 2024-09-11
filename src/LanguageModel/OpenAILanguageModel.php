<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

class OpenAILanguageModel implements LanguageModelInterface
{
    private Client $client;

    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-3.5-turbo',
        private Logger $logger
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function generateResponse(string $prompt, array $context): string
    {
        try {
            $this->logger->info("Generating response from OpenAI", ['model' => $this->model]);
            $messages = $this->formatMessages($prompt, $context);

            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 150,
                    'top_p' => 1,
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0,
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

    private function formatMessages(string $prompt, array $context): array
    {
        return [
            ['role' => 'system', 'content' => 'You are a helpful assistant. Use the provided context to answer the user\'s question.'],
            ['role' => 'user', 'content' => "Context:\n" . json_encode($context)],
            ['role' => 'user', 'content' => "Question: $prompt"]
        ];
    }
}