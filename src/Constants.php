<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

/**
 * Every constant of the library are set here.
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
     * @var string The Development URL of the CrowdSec CAPI
     */
    public const URL_DEV = 'https://api.dev.crowdsec.net/v2/';

    /**
     * @var string The Production URL of the CrowdSec CAPI
     */
    public const URL_PROD = 'https://api.crowdsec.net/v2/';
    /**
     * @var string The user agent prefix used to send request to CAPI
     */
    public const USER_AGENT_PREFIX = 'PHP CrowdSec CAPI client/';
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v0.0.1';
}
