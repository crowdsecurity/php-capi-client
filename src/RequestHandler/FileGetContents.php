<?php

namespace CrowdSec\CapiClient\RequestHandler;

use CrowdSec\CapiClient\ClientException;
use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\HttpMessage\Response;

/**
 * file_get_contents request handler.
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
    public function handle(Request $request)
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
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : array();
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : array();
        $status = $this->getResponseHttpCode($parts);

        return new Response($responseBody, $status);
    }

    /**
     * Retrieve configuration for the stream content.
     *
     * @return array|array[]
     */
    private function createContextConfig(Request $request)
    {
        $headers = $request->getHeaders();
        if (!isset($headers['User-Agent'])) {
            throw new ClientException('User agent is required');
        }
        $header = $this->convertHeadersToString($headers);
        $method = $request->getMethod();
        $config = array(
            'http' => array(
                'method' => $method,
                'header' => $header,
                'ignore_errors' => true,
            ),
        );

        if ('POST' === strtoupper($method)) {
            $config['http']['content'] = json_encode($request->getParams());
        }

        return $config;
    }

    /**
     * Convert a key-value array of headers to the official HTTP header string.
     */
    private function convertHeadersToString(array $headers)
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
    protected function exec($url, $context)
    {
        return array('response' => file_get_contents($url, false, $context), 'header' => $http_response_header);
    }

    /**
     * @param $parts
     *
     * @return int
     */
    protected function getResponseHttpCode($parts)
    {
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) ($parts[1]);
        }

        return $status;
    }
}
