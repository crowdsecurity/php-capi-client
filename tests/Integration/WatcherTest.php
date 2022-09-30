<?php

namespace CrowdSec\CapiClient\Tests\Integration;

/**
 * Integration Test for watcher.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\AbstractClient;
use CrowdSec\CapiClient\RequestHandler\FileGetContents;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
final class WatcherTest extends TestCase
{
    public const BAD_MACHINE_ID = 'test';

    public const BAD_PASSWORD = '1234';

    public function requestHandlerProvider(): array
    {
        return [
            'Default (Curl)' => [null],
            'FileGetContents' => [new FileGetContents()],
        ];
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testRegister($requestHandler)
    {
        $machineId = getenv('MACHINE_ID');
        $password = getenv('PASSWORD');
        $this->assertNotFalse($machineId, 'Machine id must be defined');
        $this->assertNotFalse($password, 'Password must be defined');
        // Test with bad credentials
        $configs = ['machine_id' => self::BAD_MACHINE_ID, 'password' => self::BAD_MACHINE_ID];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->register();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_500 . '.*Invalid Password/',
            $response['error'],
            'Bad credentials'
        );
        // Test with already registered watcher
        $configs = ['machine_id' => $machineId, 'password' => $password];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->register();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_500 . '.*User already registered/',
            $response['error'],
            'Already registered'
        );
        // Test with already registered watcher but bad password
        $configs = ['machine_id' => $machineId, 'password' => self::BAD_PASSWORD];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->register();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_500 . '.*Invalid Password/',
            $response['error'],
            'Bad password'
        );
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testLogin($requestHandler)
    {
        $machineId = getenv('MACHINE_ID');
        $password = getenv('PASSWORD');
        $this->assertNotFalse($machineId, 'Machine id must be defined');
        $this->assertNotFalse($password, 'Password must be defined');
        // Test with bad credentials
        $configs = ['machine_id' => self::BAD_MACHINE_ID, 'password' => self::BAD_MACHINE_ID];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->login();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*too short/',
            $response['error'],
            'Bad credentials'
        );
        // Test with already registered watcher
        $configs = ['machine_id' => $machineId, 'password' => $password];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->login();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_200 . '/',
            (string) $response['code'],
            'Login ok'
        );
        // Test with already registered watcher but bad password
        $configs = ['machine_id' => $machineId, 'password' => self::BAD_PASSWORD];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->login();

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_403 . '.*password is incorrect/',
            $response['error'],
            'Bad password'
        );
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testDecisionsStream($requestHandler)
    {
        $machineId = getenv('MACHINE_ID');
        $password = getenv('PASSWORD');
        $this->assertNotFalse($machineId, 'Machine id must be defined');
        $this->assertNotFalse($password, 'Password must be defined');
        // Test with bad credentials
        $configs = ['machine_id' => self::BAD_MACHINE_ID, 'password' => self::BAD_MACHINE_ID];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->getStreamDecisions();

        PHPUnitUtil::assertRegExp(
            $this,
            '/Token is required.*' . MockedData::HTTP_400 . '.*too short/',
            $response['error'],
            'Token is required'
        );
        // Test with already registered watcher
        $configs = ['machine_id' => $machineId, 'password' => $password];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->getStreamDecisions();

        $this->assertArrayHasKey('new', $response, 'Response should have a "new" key');
        $this->assertArrayHasKey('deleted', $response, 'Response should have a "deleted" key');
    }

    /**
     * @dataProvider requestHandlerProvider
     */
    public function testPushSignals($requestHandler)
    {
        $machineId = getenv('MACHINE_ID');
        $password = getenv('PASSWORD');
        $this->assertNotFalse($machineId, 'Machine id must be defined');
        $this->assertNotFalse($password, 'Password must be defined');
        // Test with bad credentials
        $configs = ['machine_id' => self::BAD_MACHINE_ID, 'password' => self::BAD_MACHINE_ID];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $response = $client->getStreamDecisions();

        PHPUnitUtil::assertRegExp(
            $this,
            '/Token is required.*' . MockedData::HTTP_400 . '.*too short/',
            $response['error'],
            'Token is required'
        );
        // Test with already registered watcher
        $configs = ['machine_id' => $machineId, 'password' => $password];
        $client = new Watcher($configs, $requestHandler);
        $this->checkRequestHandler($client, $requestHandler);
        $signals = $this->getSignals($machineId);
        $response = $client->pushSignals($signals);

        PHPUnitUtil::assertRegExp(
            $this,
            '/OK/',
            $response['message'],
            'Signals should be pushed'
        );
    }

    /**
     * @param $requestHandler
     *
     * @return void
     */
    private function checkRequestHandler(AbstractClient $client, $requestHandler)
    {
        if (null === $requestHandler) {
            $this->assertEquals(
                'CrowdSec\CapiClient\RequestHandler\Curl',
                get_class($client->getRequestHandler()),
                'Request handler should be curl by default'
            );
        } else {
            $this->assertEquals(
                'CrowdSec\CapiClient\RequestHandler\FileGetContents',
                get_class($client->getRequestHandler()),
                'Request handler should be file_get_contents'
            );
        }
    }

    /**
     * @param $machineId
     *
     * @return array[]
     */
    private function getSignals($machineId): array
    {
        return [
            0 => [
                'machine_id' => $machineId,
                'message' => 'Ip 1.1.1.1 performed "crowdsecurity / http - path - traversal - probing" (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338',
                'scenario' => 'crowdsecurity/http-path-traversal-probing',
                'scenario_hash' => '',
                'scenario_version' => '',
                'source' => [
                    'id' => 1,
                    'as_name' => 'CAPI CLIENT PHP INTEGRATION TEST',
                    'cn' => 'FR',
                    'ip' => '1.1.1.1',
                    'latitude' => 48.9917,
                    'longitude' => 1.9097,
                    'range' => '1.1.1.1/32',
                    'scope' => 'test',
                    'value' => '1.1.1.1',
                ],
                'start_at' => '2020-11-06T20:13:41.196817737Z',
                'stop_at' => '2020-11-06T20:14:11.189252228Z',
            ],
            1 => [
                'machine_id' => $machineId,
                'message' => 'Ip 2.2.2.2 performed "crowdsecurity / http - probing" (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338',
                'scenario' => 'crowdsecurity/http-probing',
                'scenario_hash' => '',
                'scenario_version' => '',
                'source' => [
                    'id' => 2,
                    'as_name' => 'CAPI CLIENT PHP INTEGRATION TEST',
                    'cn' => 'FR',
                    'ip' => '2.2.2.2',
                    'latitude' => 48.9917,
                    'longitude' => 1.9097,
                    'range' => '2.2.2.2/32',
                    'scope' => 'test',
                    'value' => '2.2.2.2',
                ],
                'start_at' => '2020-11-06T20:13:41.196817737Z',
                'stop_at' => '2020-11-06T20:14:11.189252228Z',
            ],
        ];
    }
}
