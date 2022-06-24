<?php

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for watcher.
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
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\Tests\MockedData;
use CrowdSec\CapiClient\Tests\PHPUnitUtil;
use CrowdSec\CapiClient\Watcher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\CapiClient\AbstractClient::__construct
 * @covers \CrowdSec\CapiClient\AbstractClient::getUrl
 * @covers \CrowdSec\CapiClient\AbstractClient::getConfig
 * @covers \CrowdSec\CapiClient\AbstractClient::getRequestHandler
 * @covers \CrowdSec\CapiClient\AbstractClient::formatResponseBody
 * @covers \CrowdSec\CapiClient\AbstractClient::getFullUrl
 *
 * @uses \CrowdSec\CapiClient\HttpMessage\Response
 */
final class AbstractClientTest extends TestCase
{
    public function testClientInit()
    {
        $configs = array('machine_id' => 'test', 'password' => 'test-password');
        $client = new Watcher($configs);

        $url = $client->getUrl();
        $this->assertEquals(
            Constants::DEV_URL,
            $url,
            'Url should be dev by default'
        );
        $this->assertEquals(
            '/',
            substr($url, -1),
            'Url should end with /'
        );

        $config = $client->getConfig('password');
        $this->assertEquals(
            'test-password',
            $config,
            'Config should be set'
        );

        $requestHandler = $client->getRequestHandler();
        $this->assertEquals(
            'CrowdSec\CapiClient\RequestHandler\Curl',
            get_class($requestHandler),
            'Request handler must be curl by default'
        );

        $client = new Watcher(array_merge($configs, array('prod' => true)));
        $url = $client->getUrl();
        $this->assertEquals(
            Constants::PROD_URL,
            $url,
            'Url should be prod if specified'
        );
        $this->assertEquals(
            '/',
            substr($url, -1),
            'Url should end with /'
        );

        $error = false;
        if (\PHP_VERSION_ID < 70000) {
            try {
                new Watcher($configs, new \DateTime());
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            try {
                new Watcher($configs, new \DateTime());
            } catch (\TypeError $e) {
                $error = $e->getMessage();
            }
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/must .*RequestHandlerInterface/',
            $error,
            'Bad request handler should throw an error'
        );
    }

    public function testPrivateOrProtectedMethods()
    {
        $configs = array('machine_id' => 'test', 'password' => 'test');
        $client = new Watcher($configs);

        $fullUrl = PHPUnitUtil::callMethod(
            $client,
            'getFullUrl',
            array('/test-endpoint')
        );
        $this->assertEquals(
            Constants::DEV_URL . 'test-endpoint',
            $fullUrl,
            'Full Url should be ok'
        );

        $jsonBody = json_encode(array('message' => 'ok'));

        $response = new Response($jsonBody, 200);

        $formattedResponse = array('message' => 'ok');

        $validateResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            array($response)
        );
        $this->assertEquals(
            $formattedResponse,
            $validateResponse,
            'Array response should be valid'
        );

        $jsonBody = '{bad response]]]';
        $response = new Response($jsonBody, 200);
        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                array($response)
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/not a valid json/',
            $error,
            'Bad JSON should be detected'
        );

        $response = new Response(MockedData::REGISTER_ALREADY, 200);

        $decodedResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            array($response)
        );

        $this->assertEquals(
            array('message' => 'User already registered.'),
            $decodedResponse,
            'Decoded response should be correct'
        );

        $response = new Response(MockedData::UNAUTHORIZED, 403);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                array($response)
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/403.*Unauthorized/',
            $error,
            'Should throw error on 403'
        );

        $response = new Response(null, 200);

        $error = false;
        try {
            $decoded = PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                array($response)
            );
        } catch (ClientException $e) {
            $error = true;
        }

        $this->assertEquals(
            false,
            $error,
            'An empty response body should not throw error'
        );

        $this->assertEquals(
            array('message' => ''),
            $decoded,
            'An empty response body should not return some array'
        );

        $response = new Response(null, 500);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                array($response)
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/500.*/',
            $error,
            'An empty response body should throw error for bad status'
        );

        $response = new Response(array('test'), 200);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                array($response)
            );
        } catch (ClientException $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/Body response must be a string./',
            $error,
            'If response body is not a string it should throw error'
        );
    }
}
