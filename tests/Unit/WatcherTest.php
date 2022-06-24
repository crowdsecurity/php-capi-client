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
    protected $configs = array('machine_id' => 'MACHINE_ID', 'password' => 'MACHINE_PASSWORD');

    public function testRegisterParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('request'))
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with('POST', Watcher::REGISTER_ENDPOINT, $this->configs, array());
        $mockClient->register();
    }

    public function testLoginParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('request'))
            ->getMock();
        $mockClient->expects($this->exactly(1))->method('request')
            ->with('POST', Watcher::LOGIN_ENDPOINT, $this->configs, array());
        $mockClient->login();
    }

    public function testSignalsParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('request', 'login'))
            ->getMock();

        $signals = array('test');
        $mockClient->method('login')->will($this->returnValue(array('token' => 'test-token')));
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                array('POST', Watcher::SIGNALS_ENDPOINT, $signals, array('Authorization' => 'Bearer test-token'))
            );
        $mockClient->pushSignals($signals);
    }

    public function testDecisionsStreamParams()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('request', 'login'))
            ->getMock();

        $mockClient->method('login')->will($this->returnValue(array('token' => 'test-token')));
        $mockClient->expects($this->exactly(1))->method('request')
            ->withConsecutive(
                array('GET', Watcher::DECISIONS_STREAM_ENDPOINT, array(), array('Authorization' => 'Bearer test-token'))
            );
        $mockClient->getStreamDecisions();
    }

    public function testDecisionsStreamError()
    {
        $mockClient = $this->getMockBuilder('CrowdSec\CapiClient\Watcher')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('request'))
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
            ->setConstructorArgs(array('configs' => $this->configs))
            ->setMethods(array('sendRequest'))
            ->getMock();

        $mockClient->expects($this->exactly(1))->method('sendRequest')->will($this->returnValue(
            new Response(MockedData::LOGIN_SUCCESS, MockedData::HTTP_200, array())
        ));

        $response = $mockClient->request('POST', Watcher::LOGIN_ENDPOINT, $this->configs, array());

        $this->assertEquals(
            json_decode(MockedData::LOGIN_SUCCESS, true),
            $response,
            'Should format response as expected'
        );

        $error = false;
        try {
            $mockClient->request('PUT', Watcher::LOGIN_ENDPOINT, $this->configs, array());
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
