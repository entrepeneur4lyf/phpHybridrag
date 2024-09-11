<?php

return [
    'chromadb' => [
        'host' => getenv('CHROMADB_HOST'),
        'port' => getenv('CHROMADB_PORT'),
        'persistence' => true,
        'collection' => getenv('CHROMADB_COLLECTION'),
    ],
    'arangodb' => [
        'host' => getenv('ARANGODB_HOST'),
        'port' => getenv('ARANGODB_PORT'),
        'database' => getenv('ARANGODB_DATABASE'),
        'username' => getenv('ARANGODB_USERNAME'),
        'password' => getenv('ARANGODB_PASSWORD'),
    ],
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY'),
        'embedding_model' => getenv('OPENAI_EMBEDDING_MODEL'),
        'language_model' => getenv('OPENAI_LANGUAGE_MODEL'),
        'cache_ttl' => 86400,
    ],
    'document_preprocessing' => [
        'default_chunk_size' => 1000,
        'default_chunk_overlap' => 200,
    ],
    'vectorrag' => [
        'default_top_k' => 5,
    ],
    'graphrag' => [
        'max_depth' => 2,
        'entity_similarity_threshold' => 0.7,
    ],
    'hybridrag' => [
        'vector_weight' => 0.5,
        'top_k' => 5,
        'max_depth' => 2,
    ],
    'reranker' => [
        'bm25_weight' => 0.5,
        'semantic_weight' => 0.5,
        'top_k' => 10,
    ],
    'sentiment_analysis' => [
        'lexicon_path' => dirname(__FILE__) . '/default_lexicon.json',
    ],
    'logging' => [
        'name' => 'hybridrag',
        'path' => dirname(__FILE__) . '/logs/hybridrag.log',
        'level' => 'info',
        'debug_mode' => false,
    ],
];
