<?php

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for FGC request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\RequestHandler\FileGetContents;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @uses \CrowdSec\CapiClient\AbstractClient
 * @uses \CrowdSec\CapiClient\HttpMessage\Request
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 * @uses \CrowdSec\CapiClient\HttpMessage\AbstractMessage
 * @covers \CrowdSec\CapiClient\RequestHandler\FileGetContents::handle
 * @covers \CrowdSec\CapiClient\RequestHandler\FileGetContents::createContextConfig
 * @covers \CrowdSec\CapiClient\RequestHandler\FileGetContents::convertHeadersToString
 * @covers \CrowdSec\CapiClient\RequestHandler\FileGetContents::getResponseHttpCode
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 */
final class FileGetContentsTest extends TestCase
{
    protected $configs = array('machine_id' => 'MACHINE_ID', 'password' => 'MACHINE_PASSWORD');

    public function testContextConfig()
    {
        $method = 'POST';
        $parameters = array('machine_id' => 'test', 'password' => 'test');
        $configs = $parameters;

        $fgcRequestHandler = new FileGetContents();

        $client = new Watcher($configs, $fgcRequestHandler);
        $fgcRequester = $client->getRequestHandler();

        $request = new Request('test-url', $method, array(), $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            array($request)
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = array(
            'http' => array(
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: PHP CrowdSec CAPI client/v0.0.1
',
                'ignore_errors' => true,
                'content' => '{"machine_id":"test","password":"test"}',
            ),
        );

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for POST'
        );

        $method = 'GET';
        $parameters = array('foo' => 'bar', 'crowd' => 'sec');

        $request = new Request('test-url', $method, array(), $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            array($request)
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = array(
            'http' => array(
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: PHP CrowdSec CAPI client/v0.0.1
',
                'ignore_errors' => true,
            ),
        );

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for GET'
        );
    }

    public function testRegister()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                array('response' => MockedData::REGISTER_ALREADY, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_500)),
                array('response' => MockedData::SUCCESS, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_200 . ' OK')),
                array('response' => MockedData::BAD_REQUEST, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_400))
            )
        );

        $client = new Watcher($this->configs, $mockFGCRequest);

        $registerResponse = $client->register();
        // 500
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_500 . '.*User already registered/',
            $registerResponse['error'],
            'Already registered case'
        );

        // 200
        $registerResponse = $client->register();
        $this->assertEquals(
            'OK',
            $registerResponse['message'],
            'Success registered case'
        );
        // 400
        $registerResponse = $client->register();
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body/',
            $registerResponse['error'],
            'Bad request registered case'
        );
    }

    public function testLogin()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                array('response' => MockedData::LOGIN_SUCCESS, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_200)),
                array(
                    'response' => MockedData::LOGIN_BAD_CREDENTIALS,
                    'header' => array('HTTP/1.1 ' . MockedData::HTTP_403),
                ),
                array('response' => MockedData::BAD_REQUEST, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_400))
            )
        );
        $client = new Watcher(array(), $mockFGCRequest);

        $loginResponse = $client->login();
        // 200
        $this->assertEquals(
            'this-is-a-token',
            $loginResponse['token'],
            'Success login case'
        );
        // 403
        $loginResponse = $client->login();
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_403 . '.*The machine_id or password is incorrect/',
            $loginResponse['error'],
            'Bad credential login case'
        );

        // 400
        $loginResponse = $client->login();
        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body/',
            $loginResponse['error'],
            'Bad request login case'
        );
    }

    public function testEnsureLogin()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                array('response' => MockedData::LOGIN_SUCCESS, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_200)),
                array(
                    'response' => MockedData::LOGIN_BAD_CREDENTIALS,
                    'header' => array('HTTP/1.1 ' . MockedData::HTTP_400),
                )
            )
        );

        $client = new Watcher(array(), $mockFGCRequest);
        $tokenHeader = PHPUnitUtil::callMethod(
            $client,
            'handleTokenHeader',
            array()
        );

        $this->assertEquals(
            'Bearer this-is-a-token',
            $tokenHeader['Authorization'],
            'Header should be popuated with token'
        );

        $client = new Watcher(array(), $mockFGCRequest);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'handleTokenHeader',
                array()
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Token is required.*' . MockedData::HTTP_400 . '/',
            $error,
            'No retrieved token should throw a ClientException error'
        );
    }

    public function testSignals()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                array('response' => MockedData::LOGIN_SUCCESS, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_200)),
                array(
                    'response' => MockedData::SUCCESS,
                    'header' => array('HTTP/1.1 ' . MockedData::HTTP_200),
                ),
                array('response' => MockedData::SIGNALS_BAD_REQUEST, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_400))
            )
        );
        $client = new Watcher(array(), $mockFGCRequest);

        $signalsResponse = $client->pushSignals(array());

        $this->assertEquals(
            'OK',
            $signalsResponse['message'],
            'Success pushed signals'
        );

        $signalsResponse = $client->pushSignals(array());

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body.*scenario_hash/',
            $signalsResponse['error'],
            'Bad signals request'
        );
    }

    public function testDecisionsStream()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                array('response' => MockedData::LOGIN_SUCCESS, 'header' => array('HTTP/1.1 ' . MockedData::HTTP_200)),
                array(
                    'response' => MockedData::DECISIONS_STREAM_LIST,
                    'header' => array('HTTP/1.1 ' . MockedData::HTTP_200 . ' OK'),
                )
            )
        );

        $client = new Watcher($this->configs, $mockFGCRequest);
        $decisionsResponse = $client->getStreamDecisions();

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_STREAM_LIST, true),
            $decisionsResponse,
            'Success get decisions stream'
        );
    }

    public function testHandleError()
    {
        $mockFGCRequest = $this->getFGCMock();

        $request = new Request('test-uri', 'GET', array(), array('foo' => 'bar'));

        $mockFGCRequest->method('exec')
            ->will(
                $this->returnValue(false)
            );

        $error = false;
        try {
            $mockFGCRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'Unexpected HTTP call failure.',
            $error,
            'Should failed and throw if no response'
        );

        $request = new Request('test-uri', 'POST', array('User-Agent' => null));
        $error = false;
        try {
            $mockFGCRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'User agent is required',
            $error,
            'Should failed and throw if no user agent'
        );
    }

    public function testHandleUrl()
    {
        $mockFGCRequest = $this->getFGCMock();

        $request = new Request('test-uri', 'GET', array(), array('foo' => 'bar'));

        $mockFGCRequest->method('exec')
            ->will(
                $this->returnValue(array('response' => 'ok'))
            );

        $mockFGCRequest->expects($this->exactly(1))->method('exec')
            ->withConsecutive(
                array('test-uri?foo=bar')
            );
        $mockFGCRequest->handle($request);
    }

    protected function getFGCMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\RequestHandler\FileGetContents')
            ->setMethods(array('exec'))
            ->getMock();
    }
}
