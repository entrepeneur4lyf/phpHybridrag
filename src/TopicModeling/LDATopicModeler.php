<?php

declare(strict_types=1);

namespace HybridRAG\TopicModeling;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;

/**
 * Class LDATopicModeler
 *
 * This class implements Latent Dirichlet Allocation (LDA) for topic modeling.
 */
class LDATopicModeler
{
    private TokenCountVectorizer $vectorizer;
    private TfIdfTransformer $transformer;
    private int $numTopics;
    private int $maxIterations;

    /**
     * LDATopicModeler constructor.
     *
     * @param int $numTopics The number of topics to model
     * @param int $maxIterations The maximum number of iterations for the LDA algorithm
     */
    public function __construct(int $numTopics = 10, int $maxIterations = 100)
    {
        $this->vectorizer = new TokenCountVectorizer(new WordTokenizer());
        $this->transformer = new TfIdfTransformer();
        $this->numTopics = $numTopics;
        $this->maxIterations = $maxIterations;
    }

    /**
     * Fit the LDA model to the given documents.
     *
     * @param array $documents An array of documents to fit the model to
     * @return array The fitted model results
     */
    public function fit(array $documents): array
    {
        $counts = $this->vectorizer->fit($documents)->transform($documents);
        $tfidf = $this->transformer->fit($counts)->transform($counts);

        $vocabSize = count($this->vectorizer->getVocabulary());
        $docTopicDist = $this->initializeRandomDistribution(count($documents), $this->numTopics);
        $topicWordDist = $this->initializeRandomDistribution($this->numTopics, $vocabSize);

        for ($i = 0; $i < $this->maxIterations; $i++) {
            $docTopicDist = $this->updateDocTopicDist($tfidf, $topicWordDist);
            $topicWordDist = $this->updateTopicWordDist($tfidf, $docTopicDist);
        }

        return [
            'doc_topic_dist' => $docTopicDist,
            'topic_word_dist' => $topicWordDist,
            'vocabulary' => $this->vectorizer->getVocabulary(),
        ];
    }

    /**
     * Initialize a random distribution.
     *
     * @param int $rows The number of rows in the distribution
     * @param int $cols The number of columns in the distribution
     * @return array The initialized random distribution
     */
    private function initializeRandomDistribution(int $rows, int $cols): array
    {
        $dist = [];
        for ($i = 0; $i < $rows; $i++) {
            $dist[$i] = [];
            $sum = 0;
            for ($j = 0; $j < $cols; $j++) {
                $dist[$i][$j] = mt_rand() / mt_getrandmax();
                $sum += $dist[$i][$j];
            }
            for ($j = 0; $j < $cols; $j++) {
                $dist[$i][$j] /= $sum;
            }
        }
        return $dist;
    }

    /**
     * Update the document-topic distribution.
     *
     * @param array $tfidf The TF-IDF matrix
     * @param array $topicWordDist The topic-word distribution
     * @return array The updated document-topic distribution
     */
    private function updateDocTopicDist(array $tfidf, array $topicWordDist): array
    {
        $docTopicDist = [];
        foreach ($tfidf as $docId => $doc) {
            $docTopicDist[$docId] = [];
            $sum = 0;
            for ($topic = 0; $topic < $this->numTopics; $topic++) {
                $docTopicDist[$docId][$topic] = $this->calculateTopicLikelihood($doc, $topicWordDist[$topic]);
                $sum += $docTopicDist[$docId][$topic];
            }
            for ($topic = 0; $topic < $this->numTopics; $topic++) {
                $docTopicDist[$docId][$topic] /= $sum;
            }
        }
        return $docTopicDist;
    }

    /**
     * Update the topic-word distribution.
     *
     * @param array $tfidf The TF-IDF matrix
     * @param array $docTopicDist The document-topic distribution
     * @return array The updated topic-word distribution
     */
    private function updateTopicWordDist(array $tfidf, array $docTopicDist): array
    {
        $topicWordDist = array_fill(0, $this->numTopics, array_fill(0, count($this->vectorizer->getVocabulary()), 0));
        foreach ($tfidf as $docId => $doc) {
            foreach ($doc as $wordId => $count) {
                for ($topic = 0; $topic < $this->numTopics; $topic++) {
                    $topicWordDist[$topic][$wordId] += $count * $docTopicDist[$docId][$topic];
                }
            }
        }
        foreach ($topicWordDist as &$topic) {
            $sum = array_sum($topic);
            foreach ($topic as &$wordProb) {
                $wordProb /= $sum;
            }
        }
        return $topicWordDist;
    }

    /**
     * Calculate the likelihood of a document given a topic.
     *
     * @param array $doc The document vector
     * @param array $topicWordDist The topic-word distribution for a specific topic
     * @return float The calculated likelihood
     */
    private function calculateTopicLikelihood(array $doc, array $topicWordDist): float
    {
        $likelihood = 1.0;
        foreach ($doc as $wordId => $count) {
            $likelihood *= pow($topicWordDist[$wordId], $count);
        }
        return $likelihood;
    }
}
