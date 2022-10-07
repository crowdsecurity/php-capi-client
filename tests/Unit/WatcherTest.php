<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for watcher requests.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use Exception;

/**
 * @uses \CrowdSec\CapiClient\AbstractClient
 * @uses \CrowdSec\CapiClient\Storage\FileStorage
 * @uses \CrowdSec\CapiClient\Watcher::refreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::shouldLogin
 * @uses \CrowdSec\CapiClient\Watcher::handleLogin
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 * @uses \CrowdSec\CapiClient\HttpMessage\Request
 *
 * @covers \CrowdSec\CapiClient\Watcher::__construct
 * @covers \CrowdSec\CapiClient\Watcher::configure
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::manageRequest
 * @covers \CrowdSec\CapiClient\Watcher::ensureRegister
 * @covers \CrowdSec\CapiClient\Watcher::ensureAuth
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\Watcher::enroll
 * @covers \CrowdSec\CapiClient\AbstractClient::request
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::formatUserAgent
 * @covers \CrowdSec\CapiClient\Watcher::areEquals
 * @covers \CrowdSec\CapiClient\Watcher::generatePassword
 * @covers \CrowdSec\CapiClient\Watcher::generateRandomString
 * @covers \CrowdSec\CapiClient\Watcher::generateMachineId
 * @covers \CrowdSec\CapiClient\Watcher::shouldRefreshCredentials
 * @covers \CrowdSec\CapiClient\Configuration::getConfigTreeBuilder
 */
class WatcherTest extends AbstractClient
{
    public function testRegisterParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        // Set null password to force register
        $mockFileStorage->method('retrievePassword')->willReturn(
            null
        );

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with(
                'POST',
                Watcher::REGISTER_ENDPOINT,
                self::callback(function ($params): bool {
                    return 2 === count($params) &&
                           !empty($params['password']) &&
                           Watcher::PASSWORD_LENGTH === strlen($params['password']) &&
                           !empty($params['machine_id']) &&
                           Watcher::MACHINE_ID_LENGTH === strlen($params['machine_id']) &&
                           0 === substr_compare(
                               $params['machine_id'],
                               TestConstants::MACHINE_ID_PREFIX,
                               0,
                               strlen(TestConstants::MACHINE_ID_PREFIX)
                           );
                }), ['User-Agent' => Constants::USER_AGENT_PREFIX . Constants::VERSION . '/' .
                                     TestConstants::USER_AGENT_SUFFIX, ]
            );

        PHPUnitUtil::callMethod(
            $mockClient,
            'ensureRegister',
            []
        );
    }

    public function testLoginParams()
    {
        $mockFileStorage = $this->getFileStorageMock();

        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        // Set null token to force login
        $mockFileStorage->method('retrieveToken')->willReturn(
            null
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with(
                'POST',
                Watcher::LOGIN_ENDPOINT,
                [
                    'password' => TestConstants::PASSWORD,
                    'machine_id' => TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID,
                    'scenarios' => TestConstants::SCENARIOS,
                ],
                [
                    'User-Agent' => Constants::USER_AGENT_PREFIX .
                                    Constants::VERSION . '/' . TestConstants::USER_AGENT_SUFFIX,
                ]
            );
        $code = 0;
        $message = '';
        try {
            PHPUnitUtil::callMethod(
                $mockClient,
                'ensureAuth',
                []
            );
        } catch (ClientException $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
        }
        $this->assertEquals(401, $code);
        $this->assertEquals('Login response does not contain required token.', $message);
    }

    public function testSignalsParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $signals = ['test'];

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'POST',
                    Watcher::SIGNALS_ENDPOINT,
                    $signals,
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX .
                                        Constants::VERSION . '/' . TestConstants::USER_AGENT_SUFFIX,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->pushSignals($signals);
    }

    public function testDecisionsStreamParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'GET',
                    Watcher::DECISIONS_STREAM_ENDPOINT,
                    [],
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX .
                                        Constants::VERSION . '/' . TestConstants::USER_AGENT_SUFFIX,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->getStreamDecisions();
    }

    public function testEnrollParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            TestConstants::PASSWORD
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . TestConstants::MACHINE_ID
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            TestConstants::TOKEN
        );
        $mockFileStorage->method('retrieveScenarios')->willReturn(
            TestConstants::SCENARIOS
        );
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['request'])
            ->getMock();

        $testName = 'test-name';
        $testOverwrite = true;
        $testEnrollKey = 'test-enroll-id';
        $testTags = ['tag1', 'tag2'];
        $params = [
            'name' => $testName,
            'overwrite' => $testOverwrite,
            'attachment_key' => $testEnrollKey,
            'tags' => $testTags,
        ];
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                [
                    'POST',
                    Watcher::ENROLL_ENDPOINT,
                    $params,
                    [
                        'User-Agent' => Constants::USER_AGENT_PREFIX .
                                        Constants::VERSION . '/' . TestConstants::USER_AGENT_SUFFIX,
                        'Authorization' => 'Bearer ' . TestConstants::TOKEN,
                    ],
                ]
            );
        $mockClient->enroll($testName, $testOverwrite, $testEnrollKey, $testTags);
    }

    public function testRequest()
    {
        // Test a valid POST request and its return
        $mockFileStorage = $this->getFileStorageMock();

        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs, 'storage' => $mockFileStorage])
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('sendRequest')->will($this->returnValue(
            new Response(MockedData::LOGIN_SUCCESS, MockedData::HTTP_200, [])
        ));

        $response = $mockClient->request('POST', '', [], []);

        $this->assertEquals(
            json_decode(MockedData::LOGIN_SUCCESS, true),
            $response,
            'Should format response as expected'
        );
        // Test a not allowed request method (PUT)
        $error = '';
        try {
            $mockClient->request('PUT', '', [], []);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/not allowed/',
            $error,
            'Not allowed method should throw an exception before sending request'
        );
    }

    public function testConfigure()
    {
        $client = new Watcher($this->configs, new FileStorage());

        $this->assertEquals(
            Constants::ENV_DEV,
            $client->getConfig('env'),
            'Env should be configured to dev by default'
        );
        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be configured'
        );
        $this->assertEquals(
            TestConstants::MACHINE_ID_PREFIX,
            $client->getConfig('machine_id_prefix'),
            'Machine id prefix should be configured'
        );

        $this->assertEquals(
            TestConstants::USER_AGENT_SUFFIX,
            $client->getConfig('user_agent_suffix'),
            'User agent suffix should be configured'
        );

        $client = new Watcher(['scenarios' => ['test-scenario', 'test-scenario']],
            new FileStorage()
        );

        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be array unique'
        );

        $client = new Watcher(['scenarios' => ['not-numeric-key' => 'test-scenario']], new FileStorage());

        $this->assertEquals(
            TestConstants::SCENARIOS,
            $client->getConfig('scenarios'),
            'Scenarios should be indexed array'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => []], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/should have at least 1 element/',
            $error,
            'Scenarios should have at least 1 element'
        );

        $error = '';
        try {
            new Watcher(['scenarios' => ['']], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/cannot contain an empty value/',
            $error,
            'Scenarios can not contain empty value'
        );

        $error = '';
        try {
            new Watcher(['machine_id_prefix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be <= 16/',
            $error,
            'machine_id_prefix length should be <16'
        );

        $error = '';
        try {
            new Watcher(['machine_id_prefix' => 'aaaaa  a'], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Allowed chars are/',
            $error,
            'machine_id_prefix should contain allowed chars'
        );

        $client = new Watcher(['machine_id_prefix' => ''], new FileStorage());

        $this->assertEquals(
            '',
            $client->getConfig('machine_id_prefix'),
            'machine_id_prefix can be empty'
        );

        $error = '';
        try {
            new Watcher(['user_agent_suffix' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be <= 16/',
            $error,
            'user_agent_suffix length should be <16'
        );

        $error = '';
        try {
            new Watcher(['user_agent_suffix' => 'aaaaa  a'], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Allowed chars are/',
            $error,
            'user_agent_suffix should contain allowed chars'
        );

        $client = new Watcher(['user_agent_suffix' => ''], new FileStorage());

        $this->assertEquals(
            '',
            $client->getConfig('user_agent_suffix'),
            'user_agent_suffix can be empty'
        );

        $error = '';
        try {
            new Watcher(['env' => 'preprod'], new FileStorage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Permissible values:/',
            $error,
            'env should be dev or prod'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        $client = new Watcher($this->configs, new FileStorage());

        // Test areEquals
        $a = ['A', 'B'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            true,
            $result,
            '$a and $b are equals'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$b, $a]
        );
        $this->assertEquals(
            true,
            $result,
            '$b and $a are equals'
        );

        $a = ['B', 'A'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            true,
            $result,
            '$a and $b are equals'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$b, $a]
        );
        $this->assertEquals(
            true,
            $result,
            '$b and $a are equals'
        );

        $a = ['B', 'C'];
        $b = ['A', 'B'];

        $result = PHPUnitUtil::callMethod(
            $client,
            'areEquals',
            [$a, $b]
        );
        $this->assertEquals(
            false,
            $result,
            '$a and $b are different'
        );

        // Test generatePassword

        $result = PHPUnitUtil::callMethod(
            $client,
            'generatePassword',
            []
        );

        $this->assertEquals(
            Watcher::PASSWORD_LENGTH,
            strlen($result),
            'Password should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $result,
            'Password should be well formatted'
        );

        // Test generateMachineId
        $result = PHPUnitUtil::callMethod(
            $client,
            'generateMachineId',
            []
        );

        $this->assertEquals(
            Watcher::MACHINE_ID_LENGTH,
            strlen($result),
            'Machine id should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $result,
            'Machine should be well formatted'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'generateMachineId',
            [['machine_id_prefix' => 'ThisIsATest']]
        );

        $this->assertEquals(
            Watcher::MACHINE_ID_LENGTH,
            strlen($result),
            'Machine id should have right length'
        );

        PHPUnitUtil::assertRegExp(
            $this,
            '/^[A-Za-z0-9]+$/',
            $result,
            'Machine should be well formatted'
        );

        $this->assertEquals(
            'ThisIsATest',
            substr($result, 0, strlen('ThisIsATest')),
            'Machine id should begin with machine id prefix'
        );

        // Test  generateRandomString

        $error = '';
        try {
            PHPUnitUtil::callMethod(
                $client,
                'generateRandomString',
                [0]
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Length must be greater than zero/',
            $error,
            'Random string must have a length greater than 0'
        );

        // Test shouldRefreshCredentials

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            [null, 'test', []]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if no machine id'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test', null, []]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if no password'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', []]
        );

        $this->assertEquals(
            false,
            $result,
            'Should not refresh'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', ['machine_id_prefix' => 'test-prefix']]
        );

        $this->assertEquals(
            true,
            $result,
            'Should refresh if machine id prefix differs from machine id start'
        );

        $result = PHPUnitUtil::callMethod(
            $client,
            'shouldRefreshCredentials',
            ['test-machine-id', 'test-password', ['machine_id_prefix' => 'test-ma']]
        );

        $this->assertEquals(
            false,
            $result,
            'Should not refresh if machine id starts with machine id prefix'
        );
    }
}
