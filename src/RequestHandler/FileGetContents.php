<?php

namespace CrowdSec\CapiClient\RequestHandler;

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\HttpMessage\Response;

/**
 * File_get_contents request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class FileGetContents implements RequestHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws ClientException
     */
    public function handle(Request $request): Response
    {
        $config = $this->createContextConfig($request);
        $context = stream_context_create($config);

        $method = $request->getMethod();
        $parameters = $request->getParams();
        $url = $request->getUri();

        if ('GET' === strtoupper($method)) {
            if (!empty($parameters)) {
                $url .= strpos($url, '?') ? '&' : '?';
                $url .= http_build_query($parameters);
            }
        }

        $fullResponse = $this->exec($url, $context);
        $responseBody = (isset($fullResponse['response'])) ? $fullResponse['response'] : false;
        if (false === $responseBody) {
            throw new ClientException('Unexpected HTTP call failure.');
        }
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : [];
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : [];
        $status = $this->getResponseHttpCode($parts);

        return new Response($responseBody, $status);
    }

    /**
     * Retrieve configuration for the stream content.
     *
     * @param Request $request
     * @return array|array[]
     * @throws ClientException
     */
    private function createContextConfig(Request $request): array
    {
        $headers = $request->getHeaders();
        if (!isset($headers['User-Agent'])) {
            throw new ClientException('User agent is required');
        }
        $header = $this->convertHeadersToString($headers);
        $method = $request->getMethod();
        $config = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'ignore_errors' => true,
            ],
        ];

        if ('POST' === strtoupper($method)) {
            $config['http']['content'] = json_encode($request->getParams());
        }

        return $config;
    }

    /**
     * Convert a key-value array of headers to the official HTTP header string.
     */
    private function convertHeadersToString(array $headers): string
    {
        $builtHeaderString = '';
        foreach ($headers as $key => $value) {
            $builtHeaderString .= "$key: $value\r\n";
        }

        return $builtHeaderString;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $url
     * @param $context
     *
     * @return array
     */
    protected function exec($url, $context): array
    {
        return ['response' => file_get_contents($url, false, $context), 'header' => $http_response_header];
    }

    /**
     * @param $parts
     *
     * @return int
     */
    protected function getResponseHttpCode($parts): int
    {
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) $parts[1];
        }

        return $status;
    }
}
