<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CrowdSec\CapiClient\RequestHandler\FileGetContents;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

$scenarios = $argv[1] ?? null;
if (!$scenarios) {
    exit(
        'Usage: php decisions-stream.php <SCENARIOS_JSON>' . PHP_EOL .
        'Example: php decisions-stream.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' ' . PHP_EOL
    );
}
$scenarios = json_decode($scenarios);

echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = ['machine_id_prefix' => 'CapiClientTest', 'user_agent_suffix' => 'CapiClientTest'];
echo \PHP_EOL . 'Instantiate custom request handler ...' . \PHP_EOL;
$customRequestHandler = new FileGetContents();
$client = new Watcher($configs, new FileStorage(), $customRequestHandler);
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling ' . $client->getConfig('api_url') . ' for decisions stream ...' . \PHP_EOL;
echo 'Scenarios list is: ' . PHP_EOL;
print_r($scenarios);
$response = $client->getStreamDecisions($scenarios);
echo 'Decisions stream response is:' . json_encode($response) . \PHP_EOL;
