<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

interface LanguageModelInterface
{
    public function generateResponse(string $prompt, array $context): string;
}