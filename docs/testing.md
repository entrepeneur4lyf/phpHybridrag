# Testing Guide

This guide provides information on how to run tests for the HybridRAG system and how to write your own tests.

## Table of Contents

- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [Continuous Integration](#continuous-integration)

## Running Tests

To run the entire test suite, use the following command from the project root:

```bash
vendor/bin/phpunit
```

To run specific test files or directories:

```bash
vendor/bin/phpunit tests/VectorRAG
vendor/bin/phpunit tests/GraphRAG/GraphRAGTest.php
```

To run tests with code coverage:

```bash
vendor/bin/phpunit --coverage-html coverage
```

This will generate an HTML code coverage report in the `coverage` directory.

## Writing Tests

HybridRAG uses PHPUnit for testing. Here's an example of how to write a test:

```php
use PHPUnit\Framework\TestCase;
use HybridRAG\VectorRAG\VectorRAG;

class VectorRAGTest extends TestCase
{
    private VectorRAG $vectorRAG;

    protected function setUp(): void
    {
        $config = new Configuration('path/to/test/config.yaml');
        $this->vectorRAG = new VectorRAG($config);
    }

    public function testAddDocument(): void
    {
        $result = $this->vectorRAG->addDocument('test1', 'Test content', ['metadata' => 'value']);
        $this->assertTrue($result);

        $context = $this->vectorRAG->retrieveContext('Test content');
        $this->assertCount(1, $context);
        $this->assertEquals('Test content', $context[0]['content']);
    }
}
```

## Continuous Integration

HybridRAG uses GitHub Actions for continuous integration. The CI pipeline runs all tests on every push and pull request.

To set up CI for your fork or project:

1. Copy the `.github/workflows/tests.yml` file to your repository.
2. Adjust the PHP versions and any other settings as needed.
3. Make sure to set up any necessary secrets (e.g., API keys) in your repository settings.

For more information on GitHub Actions, see the [official documentation](https://docs.github.com/en/actions).

Remember to run tests locally before pushing changes to ensure they pass in your environment.
