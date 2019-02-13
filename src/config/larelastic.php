<?php

return [
    /**
     * You can specify one of several different connections when building an
     * Elasticsearch client.
     *
     * Here you may specify which of the connections below you wish to use
     * as your default connection when building an client. Of course you may
     * use create several clients at once, each with different configurations.
     *
     * You will need to set env vars for each specific index if you don't wish
     * to use the facade method to set your index each time
     */

    'default' => [
        'host' => env('ELASTICSEARCH_HOST'),
        'port' => env('ELASTICSEARCH_PORT', 9200),
        'base_url' => env('ELASTICSEARCH_HOST') . ":" . env('ELASTICSEARCH_PORT', 9200),
        'index' => env('ELASTICSEARCH_INDEX')
    ],
];
