<?php

declare(strict_types=1);

namespace HybridRAG\DocumentOrganization;

use Phpml\Clustering\KMeans;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;

class DocumentClusterer
{
    private KMeans $kmeans;
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;

    public function __construct(int $k = 5)
    {
        $this->kmeans = new KMeans($k);
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
    }

    public function cluster(array $documents): array
    {
        $tokens = array_map([$this, 'tokenize'], $documents);
        $features = $this->tfidfTransformer->fit($tokens)->transform($tokens);
        return $this->kmeans->cluster($features);
    }

    private function tokenize(string $text): array
    {
        return $this->tokenizer->tokenize(strtolower($text));
    }
}