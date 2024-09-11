<?php

return [
    'chromadb' => [
        'host' => 'localhost',
        'port' => 8000,
        'persistence' => true,
        'collection' => 'default_collection',
    ],
    'arangodb' => [
        'host' => 'localhost',
        'port' => 8529,
        'database' => 'hybridrag',
        'username' => 'root',
        'password' => '',
        'backup' => [
            'path' => '/path/to/backup/directory',
            'schedule' => '0 2 * * *', // Run backup daily at 2 AM
        ],
    ],
    'openai' => [
        'api_key' => 'your_openai_api_key_here',
        'embedding_model' => 'text-embedding-ada-002',
        'language_model' => 'gpt-3.5-turbo',
        'cache_ttl' => 86400, // 24 hours
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
];