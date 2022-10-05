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
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @uses \CrowdSec\CapiClient\AbstractClient
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 * @uses \CrowdSec\CapiClient\HttpMessage\Request
 * @uses \CrowdSec\CapiClient\Configuration::getConfigTreeBuilder
 * @uses \CrowdSec\CapiClient\Watcher::configure
 * @uses \CrowdSec\CapiClient\Watcher::ensureRegister
 * @uses \CrowdSec\CapiClient\Watcher::generateMachineId
 * @uses \CrowdSec\CapiClient\Watcher::generatePassword
 * @uses \CrowdSec\CapiClient\Watcher::generateRandomString
 * @uses \CrowdSec\CapiClient\Watcher::refreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::refreshToken
 * @uses \CrowdSec\CapiClient\Watcher::shouldRefreshCredentials
 * @uses \CrowdSec\CapiClient\Watcher::ensureAuth
 * @uses \CrowdSec\CapiClient\Watcher::manageRequest
 *
 * @covers \CrowdSec\CapiClient\Watcher::__construct
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\AbstractClient::request
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::formatUserAgent
 */
class WatcherTest extends TestCase
{
    protected $configs = [
        'machine_id_prefix' => TestConstants::MACHINE_ID_PREFIX,
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX
    ];

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
                    return count($params) === 2 &&
                           !empty($params['password']) &&
                           strlen($params['password']) === Watcher::PASSWORD_LENGTH &&
                           !empty($params['machine_id']) &&
                           strlen($params['machine_id']) === Watcher::MACHINE_ID_LENGTH &&
                           0 === substr_compare(
                               $params['machine_id'],
                               TestConstants::MACHINE_ID_PREFIX,
                               0,
                               strlen(TestConstants::MACHINE_ID_PREFIX)
                           )
                        ;
                })
                ,['User-Agent' => Constants::USER_AGENT_PREFIX . Constants::VERSION . '/' . TestConstants::USER_AGENT_SUFFIX]
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
            'test-password'
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . 'test-machine-id'
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
                    'password' => 'test-password',
                    'machine_id' => TestConstants::MACHINE_ID_PREFIX . 'test-machine-id',
                    'scenarios' => []
                ],
                [
                    'User-Agent' => Constants::USER_AGENT_PREFIX .
                                    Constants::VERSION .  '/' . TestConstants::USER_AGENT_SUFFIX
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
        $this->assertEquals('Token is required.', $message);

    }


    public function testSignalsParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            'test-password'
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . 'test-machine-id'
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            'test-token'
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
                                        Constants::VERSION .  '/' . TestConstants::USER_AGENT_SUFFIX,
                        'Authorization' => 'Bearer test-token',
                    ],
                ]
            );
        $mockClient->pushSignals($signals);
    }


    public function testDecisionsStreamParams()
    {
        $mockFileStorage = $this->getFileStorageMock();
        $mockFileStorage->method('retrievePassword')->willReturn(
            'test-password'
        );
        $mockFileStorage->method('retrieveMachineId')->willReturn(
            TestConstants::MACHINE_ID_PREFIX . 'test-machine-id'
        );
        $mockFileStorage->method('retrieveToken')->willReturn(
            'test-token'
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
                                        Constants::VERSION .  '/' . TestConstants::USER_AGENT_SUFFIX,
                        'Authorization' => 'Bearer test-token',
                    ],
                ]
            );
        $mockClient->getStreamDecisions();
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

        $response = $mockClient->request('POST', "", [], []);

        $this->assertEquals(
            json_decode(MockedData::LOGIN_SUCCESS, true),
            $response,
            'Should format response as expected'
        );
        // Test a not allowed request method (PUT)
        $error = false;
        try {
            $mockClient->request('PUT', "", [], []);
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

    protected function getFileStorageMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\Storage\FileStorage')
            ->onlyMethods(
                [
                    'retrieveToken',
                    'retrievePassword',
                    'retrieveMachineId',
                    'storePassword',
                    'storeMachineId',
                    'storeToken'
                ]
            )
            ->getMock();
    }
}
