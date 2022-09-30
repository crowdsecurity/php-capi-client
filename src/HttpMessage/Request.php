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
    protected $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

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
     * @param array $headers
     * @param array $parameters
     */
    public function __construct(string $uri, string $method, array $headers = [], array $parameters = [])
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
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retrieve the user agent used to call CAPI.
     *
     * @return string
     */
    protected function formatUserAgent(): string
    {
        return Constants::USER_AGENT_PREFIX . Constants::VERSION;
    }
}
