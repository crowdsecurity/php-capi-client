<?php

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for watcher requests.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @uses \CrowdSec\CapiClient\AbstractClient
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 * @uses \CrowdSec\CapiClient\HttpMessage\Request
 *
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\AbstractClient::request
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 */
class WatcherTest extends TestCase
{
    protected $configs = ['machine_id' => 'MACHINE_ID', 'password' => 'MACHINE_PASSWORD'];

    public function testRegisterParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with('POST', Watcher::REGISTER_ENDPOINT, $this->configs, []);
        $mockClient->register();
    }

    public function testLoginParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['request'])
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with('POST', Watcher::LOGIN_ENDPOINT, $this->configs, []);
        $mockClient->login();
    }

    public function testSignalsParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['request', 'login'])
            ->getMock();

        $signals = ['test'];
        $mockClient->method('login')->will($this->returnValue(['token' => 'test-token']));
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                ['POST', Watcher::SIGNALS_ENDPOINT, $signals, ['Authorization' => 'Bearer test-token']]
            );
        $mockClient->pushSignals($signals);
    }

    public function testDecisionsStreamParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['request', 'login'])
            ->getMock();

        $mockClient->method('login')->will($this->returnValue(['token' => 'test-token']));
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                ['GET', Watcher::DECISIONS_STREAM_ENDPOINT, [], ['Authorization' => 'Bearer test-token']]
            );
        $mockClient->getStreamDecisions();
    }

    public function testDecisionsStreamError()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['request'])
            ->getMock();

        $mockClient->method('request')->will($this->throwException(new ClientException('test-error')));
        $response = $mockClient->getStreamDecisions();

        $this->assertArrayHasKey(
            'error',
            $response,
            'Should have a well formatted response on error'
        );
    }

    public function testRequest()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(['configs' => $this->configs])
            ->setMethods(['sendRequest'])
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('sendRequest')->will($this->returnValue(
            new Response(MockedData::LOGIN_SUCCESS, MockedData::HTTP_200, [])
        ));

        $response = $mockClient->request('POST', Watcher::LOGIN_ENDPOINT, $this->configs, []);

        $this->assertEquals(
            json_decode(MockedData::LOGIN_SUCCESS, true),
            $response,
            'Should format response as expected'
        );

        $error = false;
        try {
            $mockClient->request('PUT', Watcher::LOGIN_ENDPOINT, $this->configs, []);
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
}
