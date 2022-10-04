<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

// Parse argument
$signals = $argv[1] ?? null;
if (!$signals) {
    exit('Usage: php watcher-signals.php <SIGNALS_JSON>' . \PHP_EOL);
}
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = ['machine_id_prefix' => 'CapiClientTest', 'user_agent_suffix' => 'CapiClientTest'];
$scenarios = [];
$client = new Watcher($configs, new FileStorage());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling signals for ' . $client->getConfig('api_url') . \PHP_EOL;
$response = $client->pushSignals(json_decode($signals), $scenarios);
echo 'Push signals response is:' . json_encode($response) . \PHP_EOL;
