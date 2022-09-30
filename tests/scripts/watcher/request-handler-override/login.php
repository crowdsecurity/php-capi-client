<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CrowdSec\CapiClient\RequestHandler\FileGetContents;
use CrowdSec\CapiClient\Watcher;

// Parse argument
$machineId = isset($argv[1]) ? $argv[1] : null;
$password = isset($argv[2]) ? $argv[2] : null;
if (!$machineId || !$password) {
    exit('Usage: php watcher-login.php <MACHINE_ID> <PASSWORD>' . \PHP_EOL);
}

$configs = ['machine_id' => $machineId, 'password' => $password];
echo \PHP_EOL . 'Instantiate custom request handler ...' . \PHP_EOL;
$customRequestHandler = new FileGetContents();
echo 'Instantiate watcher ...' . \PHP_EOL;
$client = new Watcher($configs, $customRequestHandler);
echo 'Watcher instantiated' . \PHP_EOL;
echo 'Calling login ...' . \PHP_EOL;
$response = $client->login();
echo 'Login response is:' . json_encode($response) . \PHP_EOL;
