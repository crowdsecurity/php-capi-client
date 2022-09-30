<?php

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for Curl request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\HttpMessage\Request;
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
 * @covers \CrowdSec\CapiClient\RequestHandler\Curl::createOptions
 * @covers \CrowdSec\CapiClient\RequestHandler\Curl::handle
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::handleTokenHeader
 * @covers \CrowdSec\CapiClient\Watcher::getStreamDecisions
 * @covers \CrowdSec\CapiClient\Watcher::register
 * @covers \CrowdSec\CapiClient\Watcher::login
 * @covers \CrowdSec\CapiClient\Watcher::pushSignals
 */
final class CurlTest extends TestCase
{
    protected $configs = ['machine_id' => 'MACHINE_ID', 'password' => 'MACHINE_PASSWORD'];

    public function testOptions()
    {
        $url = Constants::DEV_URL . 'watchers';
        $method = 'POST';
        $parameters = ['machine_id' => 'test', 'password' => 'test'];
        $configs = $parameters;

        $client = new Watcher($configs);
        $curlRequester = $client->getRequestHandler();
        $request = new Request($url, $method, [], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );
        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => Constants::USER_AGENT_PREFIX . Constants::VERSION,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . Constants::USER_AGENT_PREFIX . Constants::VERSION,
            ],
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => '{"machine_id":"test","password":"test"}',
            \CURLOPT_URL => $url,
            \CURLOPT_CUSTOMREQUEST => $method,
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for POST'
        );

        $url = Constants::DEV_URL . 'decisions/stream';
        $method = 'GET';
        $parameters = ['foo' => 'bar', 'crowd' => 'sec'];
        $client = new Watcher($configs);
        $curlRequester = $client->getRequestHandler();

        $request = new Request($url, $method, [], $parameters);

        $curlOptions = PHPUnitUtil::callMethod(
            $curlRequester,
            'createOptions',
            [$request]
        );

        $expected = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => Constants::USER_AGENT_PREFIX . Constants::VERSION,
            \CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type:application/json',
                'User-Agent:' . Constants::USER_AGENT_PREFIX . Constants::VERSION,
            ],
            \CURLOPT_POST => false,
            \CURLOPT_HTTPGET => true,
            \CURLOPT_URL => $url . '?foo=bar&crowd=sec',
            \CURLOPT_CUSTOMREQUEST => $method,
        ];

        $this->assertEquals(
            $expected,
            $curlOptions,
            'Curl options must be as expected for GET'
        );
    }

    public function testRegister()
    {
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::REGISTER_ALREADY,
                MockedData::SUCCESS,
                MockedData::BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_500, MockedData::HTTP_200, MockedData::HTTP_400)
        );

        $client = new Watcher($this->configs, $mockCurlRequest);

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
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_SUCCESS,
                MockedData::LOGIN_BAD_CREDENTIALS,
                MockedData::BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_403, MockedData::HTTP_400)
        );
        $client = new Watcher([], $mockCurlRequest);

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
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_SUCCESS,
                MockedData::LOGIN_BAD_CREDENTIALS
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_400)
        );
        $client = new Watcher([], $mockCurlRequest);
        $tokenHeader = PHPUnitUtil::callMethod(
            $client,
            'handleTokenHeader',
            []
        );

        $this->assertEquals(
            'Bearer this-is-a-token',
            $tokenHeader['Authorization'],
            'Header should be populated with token'
        );

        $client = new Watcher([], $mockCurlRequest);

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

    public function testSignals()
    {
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_SUCCESS,
                MockedData::SUCCESS,
                MockedData::SIGNALS_BAD_REQUEST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_200, MockedData::HTTP_400)
        );
        $client = new Watcher([], $mockCurlRequest);

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

    public function testDecisionsStream()
    {
        $mockCurlRequest = $this->getCurlMock();
        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                MockedData::LOGIN_SUCCESS,
                MockedData::DECISIONS_STREAM_LIST
            )
        );
        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(MockedData::HTTP_200, MockedData::HTTP_200)
        );
        $client = new Watcher($this->configs, $mockCurlRequest);
        $decisionsResponse = $client->getStreamDecisions();

        $this->assertEquals(
            json_decode(MockedData::DECISIONS_STREAM_LIST, true),
            $decisionsResponse,
            'Success get decisions stream'
        );
    }

    public function testHandleError()
    {
        $mockCurlRequest = $this->getCurlMock();

        $request = new Request('test-uri', 'POST', ['User-Agent' => null]);
        $error = false;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'User agent is required',
            $error,
            'Should failed and throw if no user agent'
        );

        $mockCurlRequest->method('exec')->will(
            $this->onConsecutiveCalls(
                false
            )
        );

        $request = new Request('test-uri', 'POST');

        $error = false;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'Unexpected CURL call failure: ',
            $error,
            'Should failed and throw if no response'
        );

        $mockCurlRequest->method('getResponseHttpCode')->will(
            $this->onConsecutiveCalls(
                0
            )
        );

        $error = false;
        try {
            $mockCurlRequest->handle($request);
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        $this->assertEquals(
            'Unexpected empty response http code',
            $error,
            'Should failed and throw if no response status'
        );
    }

    protected function getCurlMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\RequestHandler\Curl')
            ->setMethods(['exec', 'getResponseHttpCode'])
            ->getMock();
    }
}
