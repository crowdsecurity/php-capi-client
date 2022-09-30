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
    private $configs = [
        'prod' => false,
        'machine_id' => '',
        'password' => '',
    ];

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var string[]
     */
    protected $allowedMethods = ['POST', 'GET'];

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
    public function getUrl(): string
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
     * @param array $parameters
     * @param array $headers
     * @return array
     *
     * @throws ClientException
     */
    public function request(string $method, string $endpoint, array $parameters = [], array $headers = []): array
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
     *
     * @param Request $request
     * @return Response
     */
    public function sendRequest(Request $request): Response
    {
        return $this->requestHandler->handle($request);
    }

    /**
     * Verify the response and return an array.
     *
     * @param Response $response
     * @return array
     *
     * @throws ClientException
     */
    private function formatResponseBody(Response $response): array
    {
        $statusCode = $response->getStatusCode();

        $body = $response->getJsonBody();
        $decoded = ['message' => ''];
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
    private function getFullUrl(string $endpoint): string
    {
        return $this->getUrl() . ltrim($endpoint, '/');
    }
}
