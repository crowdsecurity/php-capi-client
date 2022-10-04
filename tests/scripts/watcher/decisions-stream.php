<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Watcher;
use CrowdSec\CapiClient\Storage\FileStorage;

echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = ['machine_id_prefix' => 'CapiClientTest', 'user_agent_suffix' => 'CapiClientTest'];
$client = new Watcher($configs, new FileStorage());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling '. $client->getConfig('api_url') .' for decisions stream ...' . \PHP_EOL;
$response = $client->getStreamDecisions();
echo 'Decisions stream response is:' . json_encode($response) . \PHP_EOL;
