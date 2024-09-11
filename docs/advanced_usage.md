# Advanced Usage Guide

This guide provides more detailed examples and explanations for advanced usage of the HybridRAG system.

## Table of Contents

- [Working with Different Document Types](#working-with-different-document-types)
- [Customizing the Retrieval Process](#customizing-the-retrieval-process)
- [Evaluating System Performance](#evaluating-system-performance)
- [Active Learning](#active-learning)

## Working with Different Document Types

HybridRAG supports various document types, including text, PDFs, images, audio, and video files. Here's how to work with each:

### Adding a PDF Document

```php
$hybridRAG->addDocument('pdf1', '/path/to/document.pdf', ['type' => 'pdf']);
```

### Adding an Image

```php
$hybridRAG->addImage('image1', '/path/to/image.jpg', ['type' => 'image']);
```

### Adding an Audio File

```php
$hybridRAG->addAudio('audio1', '/path/to/audio.mp3', ['type' => 'audio']);
```

### Adding a Video File

```php
$hybridRAG->addVideo('video1', '/path/to/video.mp4', ['type' => 'video']);
```

## Customizing the Retrieval Process

You can customize various aspects of the retrieval process:

### Adjusting Vector Weights

```php
$hybridRAG->setVectorWeight(0.7); // Increase emphasis on vector-based retrieval
```

### Setting Top K Results

```php
$hybridRAG->setTopK(10); // Retrieve top 10 results
```

### Modifying Graph Traversal Depth

```php
$hybridRAG->setMaxDepth(3); // Set maximum graph traversal depth to 3
```

## Evaluating System Performance

HybridRAG provides built-in evaluation metrics:

```php
$query = "What is the capital of France?";
$answer = $hybridRAG->generateAnswer($query, $context);
$relevantContext = ["Paris is the capital of France."]; // Ground truth

$evaluationReport = $hybridRAG->evaluatePerformance($query, $answer, $context, $relevantContext);

print_r($evaluationReport);
```

This will output a report with metrics such as faithfulness, relevance, and context precision/recall.

## Active Learning

Improve your model over time using active learning:

```php
$unlabeledSamples = [
    "What is the population of New York?",
    "Who invented the telephone?",
    // ... more unlabeled queries
];

$samplesToLabel = $hybridRAG->improveModel($unlabeledSamples, 5);

foreach ($samplesToLabel as $sample) {
    // Present these samples to a human annotator for labeling
    // Then use the labeled data to retrain or fine-tune your model
}
```

For more detailed information on each component and advanced configuration options, please refer to the [Components Documentation](components.md).
