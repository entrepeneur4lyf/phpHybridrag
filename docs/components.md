# Components Documentation

This document provides detailed information about the main components of the HybridRAG system and how to customize them.

## Table of Contents

- [VectorRAG](#vectorrag)
- [GraphRAG](#graphrag)
- [Reranker](#reranker)
- [LanguageModel](#languagemodel)

## VectorRAG

The VectorRAG component handles vector-based retrieval using ChromaDB.

### Customization

You can customize the VectorRAG component by adjusting its configuration:

```php
$config->vectorrag['default_top_k'] = 10; // Set default top K results
```

### Advanced Usage

```php
$vectorRAG = $hybridRAG->getVectorRAG();
$vectorRAG->addDocument('doc1', 'Content', ['custom_metadata' => 'value']);
$vectorResults = $vectorRAG->retrieveContext($query, 5, ['custom_metadata' => 'value']);
```

## GraphRAG

The GraphRAG component manages graph-based retrieval using ArangoDB.

### Customization

Adjust GraphRAG settings in the configuration:

```php
$config->graphrag['max_depth'] = 3; // Set maximum traversal depth
$config->graphrag['entity_similarity_threshold'] = 0.8; // Adjust entity similarity threshold
```

### Advanced Usage

```php
$graphRAG = $hybridRAG->getGraphRAG();
$graphRAG->addEntity('entity1', 'Entity Name', ['type' => 'person']);
$graphRAG->addRelationship('entity1', 'entity2', 'KNOWS', ['since' => '2020']);
$graphResults = $graphRAG->retrieveContext($query, 3);
```

## Reranker

The Reranker component combines and reranks results from both VectorRAG and GraphRAG.

### Customization

Configure the Reranker in the configuration file:

```php
$config->reranker['bm25_weight'] = 0.6; // Adjust BM25 weight
$config->reranker['semantic_weight'] = 0.4; // Adjust semantic weight
```

### Advanced Usage

```php
$reranker = $hybridRAG->getReranker();
$rerankedResults = $reranker->rerank($query, $combinedResults, 5);
```

## LanguageModel

The LanguageModel component generates answers based on the retrieved context.

### Customization

Adjust LanguageModel settings in the configuration:

```php
$config->openai['language_model']['model'] = 'gpt-4-turbo';
$config->openai['language_model']['temperature'] = 0.7;
```

### Advanced Usage

```php
$languageModel = $hybridRAG->getLanguageModel();
$customPrompt = "Given the context:\n{$context}\n\nAnswer the question: {$query}";
$answer = $languageModel->generateResponse($customPrompt, $context);
```

For more information on how these components work together and advanced configuration options, please refer to the [Advanced Usage Guide](advanced_usage.md).
