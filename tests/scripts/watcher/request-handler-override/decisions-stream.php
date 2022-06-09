<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CrowdSec\CapiClient\Watcher;
use CrowdSec\CapiClient\RequestHandler\FileGetContents;

// Parse argument
$machineId = isset($argv[1]) ? $argv[1] : null;
$password = isset($argv[2]) ? $argv[2] : null;
if (!$machineId || !$password ) {
    die('Usage: php decisions-stream.php <MACHINE_ID> <PASSWORD>'.PHP_EOL);
}

$configs = array('machine_id' => $machineId, 'password' => $password);
echo PHP_EOL . 'Instantiate custom request handler ...' . PHP_EOL;
$customRequestHandler = new FileGetContents();
echo 'Instantiate watcher ...'.PHP_EOL;
$client = new Watcher($configs, $customRequestHandler);
echo 'Watcher instantiated'.PHP_EOL;
echo 'Calling decisions stream ...'.PHP_EOL;
$response = $client->getStreamDecisions();
echo "Decisions stream response is:".json_encode($response).PHP_EOL;




