<?php

// phpcs:disable PSR12.Properties.ConstantVisibility.NotFound

namespace CrowdSec\CapiClient;

/**
 * Every constant of the library are set here.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Constants
{
    /**
     * @var string The Development URL of the CrowdSec CAPI
     */
    const DEV_URL = 'https://api.dev.crowdsec.net/v2/';

    /**
     * @var string The Production URL of the CrowdSec CAPI
     */
    const PROD_URL = 'https://api.crowdsec.net/v2/';

    /**
     * @var string The current version of this library
     */
    const VERSION = 'v0.0.1';

    /**
     * @var string The user agent prefix used to send request to CAPI
     */
    const USER_AGENT_PREFIX = 'PHP CrowdSec CAPI client/';
}
