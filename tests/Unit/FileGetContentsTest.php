<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for FGC request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\RequestHandler\FileGetContents;
use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @uses \CrowdSec\CapiClient\AbstractClient
 * @uses \CrowdSec\CapiClient\HttpMessage\Request
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 * @uses \CrowdSec\CapiClient\HttpMessage\AbstractMessage
 *
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
    protected $configs = ['machine_id' => 'MACHINE_ID', 'password' => 'MACHINE_PASSWORD'];

    public function testContextConfig()
    {
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];
        $configs = $parameters;

        $fgcRequestHandler = new FileGetContents();

        $client = new Watcher($configs, $fgcRequestHandler);
        $fgcRequester = $client->getRequestHandler();

        $request = new Request('test-url', $method, ['User-Agent' => TestConstants::USER_AGENT], $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            [$request]
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = [
            'http' => [
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: ' . TestConstants::USER_AGENT . '
',
                'ignore_errors' => true,
                'content' => '{"machine_id":"test","password":"test"}',
            ],
        ];

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for POST'
        );

        $method = 'GET';
        $parameters = ['foo' => 'bar', 'crowd' => 'sec'];

        $request = new Request('test-url', $method, ['User-Agent' => TestConstants::USER_AGENT], $parameters);

        $contextConfig = PHPUnitUtil::callMethod(
            $fgcRequester,
            'createContextConfig',
            [$request]
        );

        $contextConfig['http']['header'] = str_replace("\r", '', $contextConfig['http']['header']);

        $expected = [
            'http' => [
                'method' => $method,
                'header' => 'Accept: application/json
Content-Type: application/json
User-Agent: ' . TestConstants::USER_AGENT . '
',
                'ignore_errors' => true,
            ],
        ];

        $this->assertEquals(
            $expected,
            $contextConfig,
            'Context config must be as expected for GET'
        );
    }

    public function testDecisionsStream()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['response' => MockedData::LOGIN_SUCCESS, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_200]],
                [
                    'response' => MockedData::DECISIONS_STREAM_LIST,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_200 . ' OK'],
                ]
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

    public function testEnsureLogin()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['response' => MockedData::LOGIN_SUCCESS, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_200]],
                [
                    'response' => MockedData::LOGIN_BAD_CREDENTIALS,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_400],
                ]
            )
        );

        $client = new Watcher([], $mockFGCRequest);
        $tokenHeader = PHPUnitUtil::callMethod(
            $client,
            'handleTokenHeader',
            []
        );

        $this->assertEquals(
            'Bearer this-is-a-token',
            $tokenHeader['Authorization'],
            'Header should be popuated with token'
        );

        $client = new Watcher([], $mockFGCRequest);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'handleTokenHeader',
                []
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

    public function testHandleError()
    {
        $mockFGCRequest = $this->getFGCMock();

        $request = new Request('test-uri', 'GET', ['User-Agent' => TestConstants::USER_AGENT], ['foo' => 'bar']);

        $error = false;
        try {
            $mockFGCRequest->method('exec')
                ->will(
                    $this->returnValue(false)
                );
            $mockFGCRequest->handle($request);
        } catch (\TypeError $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/type array./',
            $error,
            'Should failed and throw if no response'
        );

        $request = new Request('test-uri', 'POST', ['User-Agent' => null]);
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

        $request = new Request('test-uri', 'GET', ['User-Agent' => TestConstants::USER_AGENT], ['foo' => 'bar']);

        $mockFGCRequest->method('exec')
            ->will(
                $this->returnValue(['response' => 'ok'])
            );

        $mockFGCRequest->expects($this->exactly(1))->method('exec')
            ->withConsecutive(
                ['test-uri?foo=bar']
            );
        $mockFGCRequest->handle($request);
    }

    public function testLogin()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['response' => MockedData::LOGIN_SUCCESS, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_200]],
                [
                    'response' => MockedData::LOGIN_BAD_CREDENTIALS,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_403],
                ],
                ['response' => MockedData::BAD_REQUEST, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_400]]
            )
        );
        $client = new Watcher([], $mockFGCRequest);

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

    public function testRegister()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['response' => MockedData::REGISTER_ALREADY, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_500]],
                ['response' => MockedData::SUCCESS, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_200 . ' OK']],
                ['response' => MockedData::BAD_REQUEST, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_400]]
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

    public function testSignals()
    {
        $mockFGCRequest = $this->getFGCMock();
        $mockFGCRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                ['response' => MockedData::LOGIN_SUCCESS, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_200]],
                [
                    'response' => MockedData::SUCCESS,
                    'header' => ['HTTP/1.1 ' . MockedData::HTTP_200],
                ],
                ['response' => MockedData::SIGNALS_BAD_REQUEST, 'header' => ['HTTP/1.1 ' . MockedData::HTTP_400]]
            )
        );
        $client = new Watcher([], $mockFGCRequest);

        $signalsResponse = $client->pushSignals([]);

        $this->assertEquals(
            'OK',
            $signalsResponse['message'],
            'Success pushed signals'
        );

        $signalsResponse = $client->pushSignals([]);

        PHPUnitUtil::assertRegExp(
            $this,
            '/' . MockedData::HTTP_400 . '.*Invalid request body.*scenario_hash/',
            $signalsResponse['error'],
            'Bad signals request'
        );
    }

    protected function getFGCMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\RequestHandler\FileGetContents')
            ->setMethods(['exec'])
            ->getMock();
    }
}
