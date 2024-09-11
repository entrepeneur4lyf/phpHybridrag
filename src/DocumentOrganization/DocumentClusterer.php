<?php

declare(strict_types=1);

namespace HybridRAG\DocumentOrganization;

use Phpml\Clustering\KMeans;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;

/**
 * Class DocumentClusterer
 *
 * This class provides functionality to cluster documents using K-Means algorithm.
 */
class DocumentClusterer
{
    private KMeans $kmeans;
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;

    /**
     * DocumentClusterer constructor.
     *
     * @param int $k The number of clusters to create
     */
    public function __construct(int $k = 5)
    {
        $this->kmeans = new KMeans($k);
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
    }

    /**
     * Cluster the given documents.
     *
     * @param array $documents An array of documents to cluster
     * @return array The clustered documents
     */
    public function cluster(array $documents): array
    {
        $tokens = array_map([$this, 'tokenize'], $documents);
        $features = $this->tfidfTransformer->fit($tokens)->transform($tokens);
        return $this->kmeans->cluster($features);
    }

    /**
     * Tokenize the given text.
     *
     * @param string $text The text to tokenize
     * @return array An array of tokens
     */
    private function tokenize(string $text): array
    {
        return $this->tokenizer->tokenize(strtolower($text));
    }
}
