<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Client;

use CrowdSec\CapiClient\Client\ListHandler\ListHandlerInterface;
use CrowdSec\CapiClient\Client\ListHandler\Curl;
use Psr\Log\LoggerInterface;
use CrowdSec\Common\Client\AbstractClient as CommonAbstractClient;

/**
 * The low level CrowdSec CAPI Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient extends CommonAbstractClient
{
    private $listHandler;

    public function __construct(
        array $configs,
        ListHandlerInterface $listHandler = null,
        LoggerInterface $logger = null
    ) {
        $this->configs = $configs;
        $this->listHandler = ($listHandler) ?: new Curl($this->configs);
        parent::__construct($configs, $this->listHandler, $logger);
    }

    /**
     * @return ListHandlerInterface
     */
    public function getListHandler(): ListHandlerInterface
    {
        return $this->listHandler;
    }
}
