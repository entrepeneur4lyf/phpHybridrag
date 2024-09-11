<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

/**
 * Interface LanguageModelInterface
 *
 * Defines the contract for language model implementations in the HybridRAG system.
 */
interface LanguageModelInterface
{
    /**
     * Generate a response based on the given prompt and context.
     *
     * @param string $prompt The input prompt
     * @param array $context Additional context for the generation
     * @return string The generated response
     */
    public function generateResponse(string $prompt, array $context): string;
}
