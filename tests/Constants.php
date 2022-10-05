<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests;

/**
 * Every constant for testing.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Constants
{
    /**
     * @var string The user agent suffix used to send request to CAPI
     */
    public const USER_AGENT_SUFFIX = 'PHPCAPITEST';

    /**
     * @var string The machine id prefix used to send request to CAPI
     */
    public const MACHINE_ID_PREFIX = "CapiClientTest";
}
