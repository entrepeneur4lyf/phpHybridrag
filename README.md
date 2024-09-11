# HybridRAG

HybridRAG is a PHP library that implements a Hybrid Retrieval-Augmented Generation system, combining vector-based and graph-based approaches for improved information retrieval and answer generation.

## Table of Contents

- [HybridRAG](#hybridrag)
  - [Table of Contents](#table-of-contents)
  - [Installation](#installation)
    - [Requirements](#requirements)
    - [Composer Installation](#composer-installation)
    - [Self-hosting ChromaDB and ArangoDB](#self-hosting-chromadb-and-arangodb)
  - [Configuration](#configuration)
  - [Usage](#usage)
    - [Basic Example](#basic-example)
    - [Advanced Examples](#advanced-examples)
  - [Components](#components)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [License](#license)

## Installation

### Requirements

- PHP 8.3 or higher
- Composer
- Docker (for self-hosting ChromaDB and ArangoDB)
- OpenAI API key (for embeddings and language model)

### Composer Installation

You can install HybridRAG via Composer. Run the following command in your project directory:

```bash
composer require your-vendor/hybrid-rag
```

### Self-hosting ChromaDB and ArangoDB

To self-host ChromaDB and ArangoDB using Docker, follow these steps:

1. Install Docker on your system if you haven't already.

2. Create a `docker-compose.yml` file in your project root with the following content:

```yaml
version: '3'
services:
  chromadb:
    image: ghcr.io/chroma-core/chroma:latest
    ports:
      - "8000:8000"
    volumes:
      - ./chroma_data:/chroma/chroma

  arangodb:
    image: arangodb:latest
    environment:
      ARANGO_ROOT_PASSWORD: your_root_password
    ports:
      - "8529:8529"
    volumes:
      - ./arango_data:/var/lib/arangodb3
```

3. Start the containers:

```bash
docker-compose up -d
```

Now ChromaDB will be available at `http://localhost:8000` and ArangoDB at `http://localhost:8529`.

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
  password: "your_root_password"

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

### Basic Example

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

### Advanced Examples

For more advanced usage examples, including working with different document types, customizing the retrieval process, and evaluating system performance, please refer to the [Advanced Usage Guide](docs/advanced_usage.md).

## Components

HybridRAG consists of several main components:

- VectorRAG: Handles vector-based retrieval
- GraphRAG: Manages graph-based retrieval
- Reranker: Combines and reranks results from both approaches
- LanguageModel: Generates answers based on the retrieved context

For detailed information about each component and how to customize them, please see the [Components Documentation](docs/components.md).

## Testing

To run the test suite, use the following command:

```bash
vendor/bin/phpunit
```

For more information on testing, including how to write and run specific tests, see the [Testing Guide](docs/testing.md).

## Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details on how to submit pull requests, report issues, and suggest improvements.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
