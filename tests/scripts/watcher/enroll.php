<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

// Parse argument
$name = $argv[1] ?? null;
$overwrite = isset($argv[2]) ? (bool) $argv[2] :  null;
$enrollKey = $argv[3] ?? null;
$tags = isset($argv[4]) ? json_decode($argv[4]) : [];
if (!$name || is_null($overwrite) || !$enrollKey ||  !$tags) {
    exit(
        'Usage: php enroll.php <NAME> <OVERWRITE> <ENROLL_KEY> <TAG_JSON>' . \PHP_EOL .
        'Example: php enroll.php  TESTWATCHER 0 ZZZZZAAAAA \'["tag1", "tag2"]\'' . PHP_EOL

    );
}
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = ['machine_id_prefix' => 'CapiClientTest', 'user_agent_suffix' => 'CapiClientTest'];
$scenarios = [];
$client = new Watcher($configs, new FileStorage());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling enroll for ' . $client->getConfig('api_url') . \PHP_EOL;
$response = $client->enroll($name, $overwrite, $enrollKey, $tags, $scenarios);
echo 'Enroll response is:' . json_encode($response) . \PHP_EOL;
