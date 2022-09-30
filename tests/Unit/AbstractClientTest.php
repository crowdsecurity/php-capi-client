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
use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use TypeError;
use const PHP_VERSION_ID;

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
        $configs = ['machine_id' => 'test', 'password' => 'test-password'];
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

        $client = new Watcher(array_merge($configs, ['prod' => true]));
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
        if (PHP_VERSION_ID < 70000) {
            try {
                new Watcher($configs, new DateTime());
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            try {
                new Watcher($configs, new DateTime());
            } catch (TypeError $e) {
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
        $configs = ['machine_id' => 'test', 'password' => 'test'];
        $client = new Watcher($configs);

        $fullUrl = PHPUnitUtil::callMethod(
            $client,
            'getFullUrl',
            ['/test-endpoint']
        );
        $this->assertEquals(
            Constants::DEV_URL . 'test-endpoint',
            $fullUrl,
            'Full Url should be ok'
        );

        $jsonBody = json_encode(['message' => 'ok']);

        $response = new Response($jsonBody, 200);

        $formattedResponse = ['message' => 'ok'];

        $validateResponse = PHPUnitUtil::callMethod(
            $client,
            'formatResponseBody',
            [$response]
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
                [$response]
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
            [$response]
        );

        $this->assertEquals(
            ['message' => 'User already registered.'],
            $decodedResponse,
            'Decoded response should be correct'
        );

        $response = new Response(MockedData::UNAUTHORIZED, 403);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
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
        $decoded=[];
        try {
            $decoded = PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
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
            ['message' => ''],
            $decoded,
            'An empty response body should not return some array'
        );

        $response = new Response(null, 500);

        $error = false;
        try {
            PHPUnitUtil::callMethod(
                $client,
                'formatResponseBody',
                [$response]
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


        $error = false;
        try {
            new Response(['test'], 200);

        } catch (TypeError $e) {
            $error = $e->getMessage();
        }

        PHPUnitUtil::assertRegExp(
            $this,
            '/must be of type .*string/',
            $error,
            'If response body is not a string it should throw error'
        );
    }
}
