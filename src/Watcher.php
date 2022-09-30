<?php

// phpcs:disable PSR12.Properties.ConstantVisibility.NotFound

namespace CrowdSec\CapiClient;

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
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     *
     * @return array
     */
    public function login(): array
    {
        try {
            $response = $this->request(
                'POST',
                self::LOGIN_ENDPOINT,
                [
                    'password' => $this->getConfig('password'),
                    'machine_id' => $this->getConfig('machine_id'), ]
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
     *
     * @return array
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
                        'machine_id' => $this->getConfig('machine_id'), ]
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
     *
     * @param array $signals
     * @return array
     */
    public function pushSignals(array $signals): array
    {
        try {
            $response = $this->request('POST', self::SIGNALS_ENDPOINT, $signals, $this->handleTokenHeader());
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     *
     * @return array
     */
    public function getStreamDecisions(): array
    {
        try {
            $response = $this->request('GET', self::DECISIONS_STREAM_ENDPOINT, [], $this->handleTokenHeader());
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
        }

        return $response;
    }

    /**
     * Handle required token (JWT) in header for next CAPI calls.
     *
     * @return array
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
}
