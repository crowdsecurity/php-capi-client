<?php

namespace CrowdSec\CapiClient\HttpMessage;

/**
 * CAPI response.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Response extends AbstractMessage
{
    /**
     * @var string|null
     */
    private $jsonBody;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @param string|null $jsonBody
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(?string $jsonBody, int $statusCode, array $headers = [])
    {
        $this->jsonBody = $jsonBody;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    /**
     * @return string|null
     */
    public function getJsonBody(): ?string
    {
        return $this->jsonBody;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
