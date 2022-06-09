<?php

// phpcs:disable PSR12.Properties.ConstantVisibility.NotFound

namespace CrowdSec\CapiClient;

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
    const LOGIN_ENDPOINT = '/watchers/login';

    const REGISTER_ENDPOINT = '/watchers';

    const SIGNALS_ENDPOINT = '/signals';

    const DECISIONS_STREAM_ENDPOINT = '/decisions/stream';

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     *
     * @return array
     */
    public function login()
    {
        try {
            $response = $this->request(
                'POST',
                self::LOGIN_ENDPOINT,
                array(
                    'password' => $this->getConfig('password'),
                    'machine_id' => $this->getConfig('machine_id'), )
            );
        } catch (\Exception $e) {
            $response = array('error' => $e->getMessage());
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
    public function register()
    {
        try {
            $response =
                $this->request(
                    'POST',
                    self::REGISTER_ENDPOINT,
                    array(
                        'password' => $this->getConfig('password'),
                        'machine_id' => $this->getConfig('machine_id'), )
                );
        } catch (\Exception $e) {
            $response = array('error' => $e->getMessage());
        }

        return $response;
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     *
     * @return array
     */
    public function pushSignals(array $signals)
    {
        try {
            $response = $this->request('POST', self::SIGNALS_ENDPOINT, $signals, $this->handleTokenHeader());
        } catch (\Exception $e) {
            $response = array('error' => $e->getMessage());
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
    public function getStreamDecisions()
    {
        try {
            $response = $this->request('GET', self::DECISIONS_STREAM_ENDPOINT, array(), $this->handleTokenHeader());
        } catch (\Exception $e) {
            $response = array('error' => $e->getMessage());
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
    private function handleTokenHeader()
    {
        if (!$this->token) {
            $loginResponse = $this->login();

            $this->token = isset($loginResponse['token']) ? $loginResponse['token'] : null;
            if (!$this->token) {
                $message = 'Token is required. ';
                if (isset($loginResponse['error'])) {
                    $message .= 'An error was detected during login: ' . $loginResponse['error'];
                }
                throw new ClientException($message);
            }
        }

        return array('Authorization' => sprintf('Bearer %s', $this->token));
    }
}
