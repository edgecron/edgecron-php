<?php

require_once __DIR__ . '/../vendor/autoload.php';

use EdgeCron\EdgeCron;
use EdgeCron\APIError;

$keyId = getenv('EDGECRON_KEY_ID');
$secret = getenv('EDGECRON_SECRET');
$client = new EdgeCron($keyId, $secret, ['base_url' => 'http://localhost:8888']);

try {
    // 1. Create endpoint
    $endpoint = $client->endpoints->create('my-webhook', 'https://httpbin.org/post');
    echo "Endpoint: {$endpoint->id}\n";

    // 2. Schedule task
    $task = $client->tasks->create($endpoint->id, '{"order_id": "ord_9102"}');
    echo "Task: {$task->id}\n";
} catch (APIError $e) {
    echo "Error: {$e->codeValue} {$e->getMessage()}\n";
}
