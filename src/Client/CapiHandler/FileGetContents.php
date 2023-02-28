<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client\CapiHandler;

use CrowdSec\Common\Constants;
use CrowdSec\Common\Client\RequestHandler\FileGetContents as CommonFileGetContents;

/**
 * FileGetContents list handler to get CAPI linked decisions (blocklists)
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileGetContents extends CommonFileGetContents implements CapiHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getListDecisions(string $url, array $headers = []): string
    {
        $config = $this->createLinkContextConfig($headers);
        $context = stream_context_create($config);

        $fullResponse = $this->exec($url, $context);
        $response= (isset($fullResponse['response'])) ? $fullResponse['response'] : false;
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : [];
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : [];
        $status = $this->getResponseHttpCode($parts);

        return $status === 200 ? (string)$response : "";
    }

    protected function exec(string $url, $context): array
    {
        return ['response' => file_get_contents($url, false, $context), 'header' => $http_response_header];
    }

    private function createLinkContextConfig(array $headers = []): array
    {
        $header = $this->convertHeadersToString($headers);
        return [
            'http' => [
                'method' => 'GET',
                'header' => $header,
                'ignore_errors' => true,
                'timeout' => Constants::API_TIMEOUT,
            ],
        ];
    }
}
