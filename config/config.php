<?php

return [
    'chromadb' => [
        'host' => getenv('CHROMADB_HOST') ?? 'localhost',
        'port' => getenv('CHROMADB_PORT') ?? 8000,
        'persistence' => true,
        'collection' => getenv('CHROMADB_COLLECTION') ?? null,
    ],
    'arangodb' => [
        'host' => getenv('ARANGODB_HOST') ?? 'localhost',
        'port' => getenv('ARANGODB_PORT') ?? 8529,
        'database' => getenv('ARANGODB_DATABASE') ?? 'hybridrag',
        'username' => getenv('ARANGODB_USERNAME') ?? null,
        'password' => getenv('ARANGODB_PASSWORD') ?? null,
    ],
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?? null,
        'api_base_url' => getenv('OPENAI_API_BASE_URL') ?? 'https://api.openai.com/v1/',
        'embedding_model' => getenv('OPENAI_EMBEDDING_MODEL') ?? 'text-embedding-3-small',
        'language_model' => [
            'model' => getenv('OPENAI_LANGUAGE_MODEL') ?? 'gpt-4-turbo', //current vision model
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ],
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
    'logging' => [
        'name' => 'hybridrag',
        'path' => dirname(__FILE__) . '/logs/hybridrag.log',
        'level' => 'info',
        'debug_mode' => false,
    ]
];
