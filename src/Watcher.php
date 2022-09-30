<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;
use Exception;

/**
 * The Watcher Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Watcher extends AbstractClient
{
    public const LOGIN_ENDPOINT = '/watchers/login';

    public const REGISTER_ENDPOINT = '/watchers';

    public const SIGNALS_ENDPOINT = '/signals';

    public const DECISIONS_STREAM_ENDPOINT = '/decisions/stream';

    /**
     * @var array
     */
    protected $configs = [
        'api_url' => Constants::DEV_URL,
        'machine_id' => '',
        'password' => '',
    ];

    /**
     * @var string
     */
    private $token = '';

    /**
     * @var string[]
     */
    private $headers;

    public function __construct(array $configs, RequestHandlerInterface $requestHandler = null)
    {
        $this->configs = array_merge($this->configs, $configs);
        $this->headers = ['User-Agent' => $this->formatUserAgent($this->configs)];
        parent::__construct($this->configs, $requestHandler);
    }

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     */
    public function login(): array
    {
        try {
            $response = $this->request(
                'POST',
                self::LOGIN_ENDPOINT,
                [
                    'password' => $this->getConfig('password'),
                    'machine_id' => $this->getConfig('machine_id'), ],
                $this->headers
            );
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Process a register call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers
     */
    public function register(): array
    {
        try {
            $response =
                $this->request(
                    'POST',
                    self::REGISTER_ENDPOINT,
                    [
                        'password' => $this->getConfig('password'),
                        'machine_id' => $this->getConfig('machine_id'), ],
                    $this->headers
                );
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     */
    public function pushSignals(array $signals): array
    {
        try {
            $headers = array_merge($this->headers, $this->handleTokenHeader());
            $response = $this->request('POST', self::SIGNALS_ENDPOINT, $signals, $headers);
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     */
    public function getStreamDecisions(): array
    {
        try {
            $headers = array_merge($this->headers, $this->handleTokenHeader());
            $response = $this->request('GET', self::DECISIONS_STREAM_ENDPOINT, [], $headers);
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Handle required token (JWT) in header for next CAPI calls.
     *
     * @throws ClientException
     */
    private function handleTokenHeader(): array
    {
        if (!$this->token) {
            $loginResponse = $this->login();

            $this->token = $loginResponse['token'] ?? null;
            if (!$this->token) {
                $message = 'Token is required. ';
                if (isset($loginResponse['error'])) {
                    $message .= 'An error was detected during login: ' . $loginResponse['error'];
                }
                throw new ClientException($message);
            }
        }

        return ['Authorization' => sprintf('Bearer %s', $this->token)];
    }

    /**
     * @param $configs
     * @return string
     */
    private function formatUserAgent(array $configs = []): string
    {
        $userAgent = Constants::USER_AGENT_PREFIX . Constants::VERSION;
        return !empty($configs['user_agent_suffix']) ? $userAgent . '/' . $configs['user_agent_suffix'] : $userAgent;
    }
}
