<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;

// Parse arguments
$scenarios = isset($argv[1]) ? json_decode($argv[1]) : null;
$signals = isset($argv[2]) ? json_decode($argv[2]) :  null;
if (is_null($signals) || is_null($scenarios)) {
    exit(
        'Usage: php signals.php <SCENARIOS_JSON> <SIGNALS_JSON>' . \PHP_EOL .
        'Example: php signals.php \'["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]\' "[{\"machine_id\":\"MACHINE_ID\",\"message\":\"Ip 1.1.1.1 performed \'crowdsecurity\/http-path-traversal-probing\' (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338\",\"scenario\":\"crowdsecurity\/http-path-traversal-probing\",\"scenario_hash\":\"\",\"scenario_version\":\"\",\"source\":{\"id\":1,\"as_name\":\"TEST\",\"cn\":\"FR\",\"ip\":\"1.1.1.1\",\"latitude\":48.9917,\"longitude\":1.9097,\"range\":\"1.1.1.1\/32\",\"scope\":\"test\",\"value\":\"1.1.1.1\"},\"start_at\":\"2020-11-06T20:13:41.196817737Z\",\"stop_at\":\"2020-11-06T20:14:11.189252228Z\"},{\"machine_id\":\"MACHINE_ID\",\"message\":\"Ip 2.2.2.2 performed \'crowdsecurity\/http-probing\' (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338\",\"scenario\":\"crowdsecurity\/http-probing\",\"scenario_hash\":\"\",\"scenario_version\":\"\",\"source\":{\"id\":2,\"as_name\":\"TEST\",\"cn\":\"FR\",\"ip\":\"2.2.2.2\",\"latitude\":48.9917,\"longitude\":1.9097,\"range\":\"2.2.2.2\/32\",\"scope\":\"test\",\"value\":\"2.2.2.2\"},\"start_at\":\"2020-11-06T20:13:41.196817737Z\",\"stop_at\":\"2020-11-06T20:14:11.189252228Z\"}]"'
    );
}
echo \PHP_EOL . 'Instantiate watcher ...' . \PHP_EOL;
$configs = [
    'machine_id_prefix' => 'CapiClientTest',
    'user_agent_suffix' => 'CapiClientTest',
    'scenarios' => $scenarios
    ];
$client = new Watcher($configs, new FileStorage());
echo 'Watcher instantiated' . \PHP_EOL;

echo 'Calling signals for ' . $client->getConfig('api_url') . \PHP_EOL;
echo 'Scenarios list is: ' . PHP_EOL;
print_r($scenarios);
$response = $client->pushSignals($signals);
echo 'Push signals response is:' . json_encode($response) . \PHP_EOL;
