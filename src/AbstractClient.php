<?php

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\RequestHandler\Curl;
use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;

/**
 * The low level REST Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var array
     */
    private $configs = array(
        'prod' => false,
        'machine_id' => '',
        'password' => '',
    );

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var string[]
     */
    protected $allowedMethods = array('POST', 'GET');

    public function __construct(array $configs, RequestHandlerInterface $requestHandler = null)
    {
        $this->configs = array_merge($this->configs, $configs);
        $this->requestHandler = ($requestHandler) ?: new Curl();
        $this->token = '';
        $this->url = $this->configs['prod'] ? Constants::PROD_URL : Constants::DEV_URL;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Retrieve a config value by name.
     *
     * @param $name
     * @param $default
     *
     * @return mixed|null
     */
    public function getConfig($name, $default = null)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : $default;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

    /**
     * Performs an HTTP request (POST, GET, ...) and returns its response body as an array.
     *
     * @param string $method
     * @param string $endpoint
     *
     * @return array
     *
     * @throws ClientException
     */
    public function request($method, $endpoint, array $parameters = array(), array $headers = array())
    {
        $method = strtoupper($method);
        if (!in_array($method, $this->allowedMethods)) {
            throw new ClientException("Method ($method) is not allowed.");
        }

        $response = $this->sendRequest(
            new Request($this->getFullUrl($endpoint), $method, $headers, $parameters)
        );

        return $this->formatResponseBody($response);
    }

    /**
     * @codeCoverageIgnore
     * @return HttpMessage\Response
     */
    public function sendRequest(Request $request)
    {
        return $this->requestHandler->handle($request);
    }

    /**
     * Verify the response and return an array.
     *
     * @return array
     *
     * @throws ClientException
     */
    private function formatResponseBody(Response $response)
    {
        $statusCode = $response->getStatusCode();

        $body = $response->getJsonBody();
        $decoded = array('message' => '');
        if (!empty($body)) {
            if (!is_string($body)) {
                throw new ClientException('Body response must be a string.');
            }

            $decoded = json_decode($response->getJsonBody(), true);

            if (null === $decoded) {
                throw new ClientException('Body response is not a valid json');
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = "Unexpected response status code: $statusCode. Body was: " . str_replace("\n", '', $body);
            throw new ClientException($message);
        }

        return $decoded;
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    private function getFullUrl($endpoint)
    {
        return $this->getUrl() . ltrim($endpoint, '/');
    }
}
