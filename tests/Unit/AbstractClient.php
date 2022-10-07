<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Abstract class for client test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

abstract class AbstractClient extends TestCase
{
    protected $configs = [
        'machine_id_prefix' => TestConstants::MACHINE_ID_PREFIX,
        'user_agent_suffix' => TestConstants::USER_AGENT_SUFFIX,
        'scenarios' => TestConstants::SCENARIOS,
    ];

    protected function getCurlMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\RequestHandler\Curl')
            ->onlyMethods(['exec', 'getResponseHttpCode'])
            ->getMock();
    }

    protected function getFileStorageMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\Storage\FileStorage')
            ->onlyMethods(
                [
                    'retrieveToken',
                    'retrievePassword',
                    'retrieveMachineId',
                    'retrieveScenarios',
                    'storePassword',
                    'storeMachineId',
                    'storeScenarios',
                    'storeToken',
                ]
            )
            ->getMock();
    }

    protected function getFGCMock()
    {
        return $this->getMockBuilder('CrowdSec\CapiClient\RequestHandler\FileGetContents')
            ->onlyMethods(['exec'])
            ->getMock();
    }
}
