# HybridRAG

HybridRAG is a PHP library that implements a Hybrid Retrieval-Augmented Generation system, combining vector-based and graph-based approaches for improved information retrieval and answer generation.

## Table of Contents

- [HybridRAG](#hybridrag)
  - [Table of Contents](#table-of-contents)
  - [Installation](#installation)
  - [Requirements](#requirements)
  - [Configuration](#configuration)
  - [Usage](#usage)
  - [Components](#components)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [License](#license)

## Installation

You can install HybridRAG via Composer. Run the following command in your project directory:

```bash
composer require your-vendor/hybrid-rag
```

## Requirements

- PHP 8.3 or higher
- Composer
- ArangoDB (for graph database)
- ChromaDB (for vector database)
- OpenAI API key (for embeddings and language model)

## Configuration

1. Copy the `config/config.yaml.example` to `config/config.yaml`.
2. Edit `config/config.yaml` and fill in your configuration details:

```yaml
openai:
  api_key: "your-openai-api-key"

arangodb:
  host: "localhost"
  port: 8529
  database: "hybrid_rag"
  username: "root"
  password: "your-password"

chromadb:
  host: "localhost"
  port: 8000
  collection: "hybrid_rag"

logging:
  name: "hybrid_rag"
  path: "/path/to/your/log/file.log"
  level: "info"
  debug_mode: false
```

## Usage

Here's a basic example of how to use HybridRAG:

```php
use HybridRAG\HybridRAG\HybridRAGFactory;
use HybridRAG\Config\Configuration;

// Load configuration
$config = new Configuration('path/to/your/config.yaml');

// Create HybridRAG instance
$hybridRAG = HybridRAGFactory::create($config);

// Add a document
$hybridRAG->addDocument('doc1', 'This is the content of the document', ['metadata' => 'value']);

// Query the system
$query = "What is the content of the document?";
$context = $hybridRAG->retrieveContext($query);
$answer = $hybridRAG->generateAnswer($query, $context);

echo $answer;
```

## Components

HybridRAG consists of several main components:

- VectorRAG: Handles vector-based retrieval
- GraphRAG: Manages graph-based retrieval
- Reranker: Combines and reranks results from both approaches
- LanguageModel: Generates answers based on the retrieved context

## Testing

To run the test suite, use the following command:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
