<?php

namespace CrowdSec\CapiClient\HttpMessage;

use CrowdSec\CapiClient\Constants;

/**
 * Request that will be sent to CAPI.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Request extends AbstractMessage
{
    /**
     * @var array
     */
    protected $headers = array(
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    );

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param string $uri
     * @param string $method
     */
    public function __construct($uri, $method, array $headers = array(), array $parameters = array())
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->headers['User-Agent'] = $this->formatUserAgent();
        $this->headers = array_merge($this->headers, $headers);
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Retrieve the user agent used to call CAPI.
     *
     * @return string
     */
    protected function formatUserAgent()
    {
        return Constants::USER_AGENT_PREFIX . Constants::VERSION;
    }
}
