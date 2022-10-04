<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;
use CrowdSec\CapiClient\Storage\StorageInterface;
use Exception;
use Symfony\Component\Config\Definition\Processor;

/**
 * The Watcher Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Watcher extends AbstractClient
{
    public const CREDENTIAL_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    public const DECISIONS_STREAM_ENDPOINT = '/decisions/stream';
    public const LOGIN_ENDPOINT = '/watchers/login';
    public const LOGIN_RETRY = 1;
    public const MACHINE_ID_LENGTH = 48;
    public const REGISTER_ENDPOINT = '/watchers';
    public const REGISTER_RETRY = 1;
    public const SIGNALS_ENDPOINT = '/signals';
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var string[]
     */
    private $headers;
    /**
     * @var string
     */
    private $machineId;
    /**
     * @var string
     */
    private $password;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var string
     */
    private $token = '';

    public function __construct(
        array                   $configs,
        StorageInterface        $storage,
        RequestHandlerInterface $requestHandler = null)
    {
        $this->configure($configs);
        $this->headers = ['User-Agent' => $this->formatUserAgent($this->configs)];
        $this->storage = $storage;
        parent::__construct($this->configs, $requestHandler);
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     * @throws ClientException
     */
    public function getStreamDecisions(): array
    {
        $this->ensureLogin();
        $loginRetry = 0;
        $lastMessage = '';
        $response = [];
        do {
            try {
                $retry = false;
                $headers = array_merge($this->headers, $this->handleTokenHeader());
                $response = $this->request('GET', self::DECISIONS_STREAM_ENDPOINT, [], $headers);
            } catch (ClientException $e) {
                $loginRetry++;
                $retry = true;
                $lastMessage = $e->getMessage();
                if ($e->getCode() === 401) {
                    $this->refreshToken();
                }
            }
        } while (($retry) && ($loginRetry <= self::LOGIN_RETRY));
        if ($loginRetry > self::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
        }

        return $response;
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     * @throws ClientException
     */
    public function pushSignals(array $signals): array
    {
        $this->ensureLogin();
        $loginRetry = 0;
        $lastMessage = '';
        $response = [];
        do {
        try {
            $retry = false;
            $headers = array_merge($this->headers, $this->handleTokenHeader());
            $response = $this->request('POST', self::SIGNALS_ENDPOINT, $signals, $headers);
        } catch (Exception $e) {
            $loginRetry++;
            $retry = true;
            $lastMessage = $e->getMessage();
            if ($e->getCode() === 401) {
                $this->refreshToken();
            }
        }
        } while (($retry) && ($loginRetry <= self::LOGIN_RETRY));
        if ($loginRetry > self::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
        }

        return $response;
    }

    /**
     * Configure this instance.
     *
     * @param array $configs An array with all configuration parameters
     *
     */
    private function configure(array $configs): void
    {
        // Process and validate input configuration.
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configs]);
    }

    private function ensureLogin(): void
    {
        $this->ensureRegister();
        $this->token = $this->storage->retrieveToken();
        if (!$this->token) {
            $this->refreshToken();
        }
    }

    private function ensureRegister(): void
    {
        $this->machineId = $this->storage->retrieveMachineId();
        $this->password = $this->storage->retrievePassword();
        if ($this->shouldRefreshCredentials()) {
            $this->refreshCredentials();
            $this->register();
        }
    }

    /**
     * @param array $configs
     * @return string
     */
    private function formatUserAgent(array $configs = []): string
    {
        $userAgent = Constants::USER_AGENT_PREFIX . Constants::VERSION;

        return !empty($configs['user_agent_suffix']) ? $userAgent . '/' . $configs['user_agent_suffix'] : $userAgent;
    }

    private function generateMachineId(): string
    {
        $prefix = !empty($this->configs['machine_id_prefix']) ? $this->configs['machine_id_prefix'] : "";
        return $prefix . $this->generateRandomString(self::MACHINE_ID_LENGTH - strlen($prefix));
    }

    /**
     * @return string
     */
    private function generatePassword(): string
    {
        return $this->generateRandomString();
    }

    /**
     * @throws Exception
     */
    private function generateRandomString(int $length = 32):string
    {
        if ($length < 1) {
            throw new Exception('Length must be greater than zero.');
        }
        $chars = self::CREDENTIAL_CHARS;
        $chLen = strlen($chars);
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $res .= $chars[random_int(0, $chLen - 1)];
        }

        return $res;

    }

    /**
     * Handle required token (JWT) in header for next CAPI calls.
     *
     * @throws ClientException
     */
    private function handleTokenHeader(): array
    {
        return ['Authorization' => sprintf('Bearer %s', $this->token)];
    }

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     *
     * @return array
     */
    private function login(): array
    {
        return $this->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [
                'password' => $this->password,
                'machine_id' => $this->machineId],
            $this->headers
        );
    }

    private function refreshCredentials(): void
    {
        $this->machineId = $this->generateMachineId();
        $this->password = $this->generatePassword();
        $this->storage->storeMachineId($this->machineId);
        $this->storage->storePassword($this->password);
    }

    /**
     * @throws ClientException
     */
    private function refreshToken():void
    {
        $loginResponse = $this->login();

        $this->token = $loginResponse['token'] ?? null;
        if (!$this->token) {
            $message = 'Token is required. ';
            if (isset($loginResponse['error'])) {
                $message .= 'An error was detected during login: ' . $loginResponse['error'];
            }
            throw new ClientException($message);
        }
        $this->storage->storeToken($this->token);
    }

    /**
     * Process a register call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers
     * @throws ClientException
     */
    private function register()
    {
        $registerRetry = 0;
        $lastMessage = '';
        do {
            try {
                $retry = false;
                $this->request(
                    'POST',
                    self::REGISTER_ENDPOINT,
                    [
                        'password' => $this->password,
                        'machine_id' => $this->machineId],
                    $this->headers
                );
            } catch (ClientException $e) {
                $registerRetry++;
                $retry = true;
                $lastMessage = $e->getMessage();
                if ($e->getCode() === 500) {
                    $this->refreshCredentials();
                }
            }
        } while (($retry) && ($registerRetry <= self::REGISTER_RETRY));
        if ($registerRetry > self::REGISTER_RETRY) {
            $message = "Could not register after $registerRetry attempts. Last error was: ";
            throw new ClientException($message. $lastMessage);
        }
    }

    private function shouldRefreshCredentials(): bool
    {
        if (!$this->machineId || !$this->password) {
            return true;
        }
        $prefix = !empty($this->configs['machine_id_prefix']) ? $this->configs['machine_id_prefix'] : null;
        // Verify that current machine_id starts with configured prefix
        if($prefix){
            return substr_compare($this->machineId , $prefix, 0, strlen($prefix)) !== 0;
        }

        return false;
    }
}
