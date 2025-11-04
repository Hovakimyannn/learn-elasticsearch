<?php

require 'vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

echo "Attempting to connect to Elasticsearch at http://localhost:9200...\n";

try {
    $client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();
} catch (AuthenticationException $e) {
    throw new RuntimeException('Authentication failed');
}

try {
    $info = $client->info();
    echo "Connection successful! Cluster name: ".$info['cluster_name']."\n";
    echo "Elasticsearch version: ".$info['version']['number']."\n";
} catch (ClientResponseException $e) {
    throw new RuntimeException('Failed to connect');
} catch (ServerResponseException $e) {
    throw new RuntimeException('Server error');
} catch (Exception $e) {
    echo "Could not connect: ".$e->getMessage()."\n";
    die();
}

echo "\n--- Indexing a new document ---\n";

$articleBody = [
    'title'      => 'Symfony 6 և Elasticsearch',
    'content'    => 'Symfony-ն ունի հիանալի աջակցություն որոնման համար։ Այն ինտեգրվում է PHP կլիենտի հետ։',
    'author'     => 'Գրիգոր',
    'tags'       => ['php', 'symfony', 'elastic'],
    'created_at' => date('Y-m-d\TH:i:s\Z')
];

$params = [
    'index' => 'articles',
    'id'    => 3,
    'body'  => $articleBody,
];

try {
    $response = $client->index($params);
    echo "Document indexed with ID 3. Result: ".$response['result']."\n";
} catch (ClientResponseException $e) {
    throw new RuntimeException('Failed to create document');
} catch (MissingParameterException $e) {
    throw new RuntimeException('Missing parameter');
} catch (ServerResponseException $e) {
    throw new RuntimeException('Server error');
} catch (Exception $e) {
    echo "Error indexing document: ".$e->getMessage()."\n";
    die();
}

//print_r($response->asArray());

echo "\n--- Searching for 'php' ---\n";

$searchQuery = [
    'query' => [
        'match' => [
            'content' => 'php'
        ]
    ]
];

$params = [
    'index' => 'articles',
    'body'  => $searchQuery
];

try {
    $response = $client->search($params);

    $totalHits = $response['hits']['total']['value'];
    echo "Total hits: ".$totalHits."\n";

    foreach ($response['hits']['hits'] as $hit) {
        $title = $hit['_source']['title'];
        $author = $hit['_source']['author'];
        $score = $hit['_score'];

        echo "Title: $title, Author: $author, Score: $score\n";
    }
} catch (Exception $e) {
    echo "Error searching: ".$e->getMessage()."\n";
}
