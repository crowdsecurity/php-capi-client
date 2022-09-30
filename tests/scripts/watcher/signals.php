<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Watcher;

// Parse argument
$machineId = isset($argv[1]) ? $argv[1] : null;
$password = isset($argv[2]) ? $argv[2] : null;
$signals = isset($argv[3]) ? $argv[3] : null;
if (!$machineId || !$password || !$signals) {
    exit('Usage: php watcher-signals.php <MACHINE_ID> <PASSWORD> <SIGNALS_JSON>' . \PHP_EOL);
}
$configs = ['machine_id' => $machineId, 'password' => $password];
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$client = new Watcher($configs);
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling signals ...' . \PHP_EOL;
$response = $client->pushSignals(json_decode($signals));
echo 'Push signals response is:' . json_encode($response) . \PHP_EOL;
