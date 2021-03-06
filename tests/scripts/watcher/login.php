<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Watcher;

// Parse argument
$machineId = isset($argv[1]) ? $argv[1] : null;
$password = isset($argv[2]) ? $argv[2] : null;
if (!$machineId || !$password) {
    die('Usage: php watcher-login.php <MACHINE_ID> <PASSWORD>' . PHP_EOL);
}

$configs = array('machine_id' => $machineId, 'password' => $password);

echo PHP_EOL . 'Instantiate watcher ...' . PHP_EOL;
$client = new Watcher($configs);
echo 'Watcher instantiated' . PHP_EOL;

echo 'Calling login ...' . PHP_EOL;
$response = $client->login();
echo "Login response is:" . json_encode($response) . PHP_EOL;