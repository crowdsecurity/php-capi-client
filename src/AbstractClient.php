<?php

declare(strict_types=1);

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
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient
{
    /**
     * @var array
     */
    protected $configs = [];
    /**
     * @var string[]
     */
    private $allowedMethods = ['POST', 'GET'];
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;
    /**
     * @var string
     */
    private $url;

    public function __construct(array $configs, RequestHandlerInterface $requestHandler = null)
    {
        $this->configs = $configs;
        $this->requestHandler = ($requestHandler) ?: new Curl();
        $this->url = $this->configs['api_url'];
    }

    /**
     * Retrieve a config value by name.
     *
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getConfig(string $name, $default = null)
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

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Performs an HTTP request (POST, GET, ...) and returns its response body as an array.
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
     */
    public function sendRequest(Request $request): Response
    {
        return $this->requestHandler->handle($request);
    }

    /**
     * Verify the response and return an array.
     *
     * @throws ClientException
     */
    private function formatResponseBody(Response $response): array
    {
        $statusCode = $response->getStatusCode();

        $body = $response->getJsonBody();
        $decoded = ['message' => ''];
        if (!empty($body)) {
            $decoded = json_decode($response->getJsonBody(), true);

            if (null === $decoded) {
                throw new ClientException('Body response is not a valid json');
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = "Unexpected response status code: $statusCode. Body was: " . str_replace("\n", '', $body);
            throw new ClientException($message, $statusCode);
        }

        return $decoded;
    }

    private function getFullUrl(string $endpoint): string
    {
        return $this->getUrl() . ltrim($endpoint, '/');
    }
}
