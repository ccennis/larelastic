<?php

return [

    'client' => [
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'null'),
        ],
        'auth_string' => 'https://' . env('ELASTICSEARCH_USERNAME') . ":" . env('ELASTICSEARCH_PASSWORD') . "@" . env('ELASTICSEARCH_HOST', 'localhost') . ":" . env('ELASTICSEARCH_PORT', '9200'),
        'base_url' => env('ELASTICSEARCH_HOST') . ":" . env('ELASTICSEARCH_PORT', 9200),
        'index' => env('ELASTICSEARCH_INDEX'),
        'username' => env('ELASTICSEARCH_USERNAME'),
        'password' => env('ELASTICSEARCH_PASSWORD'),
        'api_key' => env('ELASTICSEARCH_API_KEY'),
        'timeout_in_seconds' => env('ELASTICSEARCH_TIMEOUT', 5),
    ],
    'update_mapping' => env('ELASTIC_UPDATE_MAPPING', true),
    'indexer' => env('ELASTIC_INDEXER', 'single'),
    'document_refresh' => env('ELASTIC_DOCUMENT_REFRESH'),
];
