<?php

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for request.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Constants;
use CrowdSec\CapiClient\HttpMessage\Request;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\CapiClient\HttpMessage\Request::getParams
 * @covers \CrowdSec\CapiClient\HttpMessage\Request::getMethod
 * @covers \CrowdSec\CapiClient\HttpMessage\Request::getUri
 * @covers \CrowdSec\CapiClient\HttpMessage\Request::formatUserAgent
 * @covers \CrowdSec\CapiClient\HttpMessage\Request::__construct
 * @covers \CrowdSec\CapiClient\HttpMessage\AbstractMessage::getHeaders
 */
final class RequestTest extends TestCase
{
    public function testConstructor()
    {
        $request = new Request('test-uri', 'POST', array('test' => 'test'), array('foo' => 'bar'));

        $headers = $request->getHeaders();
        $params = $request->getParams();
        $method = $request->getMethod();
        $uri = $request->getUri();

        $this->assertEquals(
            'POST',
            $method,
            'Request method should be set'
        );

        $this->assertEquals(
            'test-uri',
            $uri,
            'Request URI should be set'
        );

        $this->assertEquals(
            array('foo' => 'bar'),
            $params,
            'Request params should be set'
        );

        $this->assertEquals(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => Constants::USER_AGENT_PREFIX . Constants::VERSION,
                'test' => 'test',
            ),
            $headers,
            'Request headers should be set'
        );
    }
}
