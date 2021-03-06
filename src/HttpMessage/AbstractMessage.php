<?php

namespace CrowdSec\CapiClient\HttpMessage;

/**
 * HTTP messages consist of requests from a client to CAPI and responses
 * from CAPI to a client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractMessage
{
    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
