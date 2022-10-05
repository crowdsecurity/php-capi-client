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
    public const PASSWORD_LENGTH = 32;
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
     * @var string|null
     */
    private $machineId;
    /**
     * @var string|null
     */
    private $password;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var string|null
     */
    private $token;

    public function __construct(
        array $configs,
        StorageInterface $storage,
        RequestHandlerInterface $requestHandler = null
    ) {
        $this->configure($configs);
        $this->headers = ['User-Agent' => $this->formatUserAgent($this->configs)];
        $this->storage = $storage;
        parent::__construct($this->configs, $requestHandler);
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     *
     * @throws ClientException
     */
    public function getStreamDecisions(array $scenarios = []): array
    {
        return $this->manageRequest('GET', self::DECISIONS_STREAM_ENDPOINT, [], $scenarios);
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     *
     * @throws ClientException
     */
    public function pushSignals(array $signals, array $scenarios = []): array
    {
        return $this->manageRequest('POST', self::SIGNALS_ENDPOINT, $signals, $scenarios);
    }

    /**
     * Configure this instance.
     *
     * @param array $configs An array with all configuration parameters
     */
    private function configure(array $configs): void
    {
        // Process and validate input configuration.
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configs]);
    }

    /**
     * Ensure that machine is registered and that we have a token.
     */
    private function ensureAuth(array $scenarios = []): void
    {
        $this->ensureRegister();
        $this->token = $this->storage->retrieveToken();
        if (!$this->token) {
            $this->refreshToken($scenarios);
        }
    }

    /**
     * Ensure that machine credentials are ready tu use.
     */
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
     * Format User-Agent header.
     */
    private function formatUserAgent(array $configs = []): string
    {
        $userAgent = Constants::USER_AGENT_PREFIX . Constants::VERSION;

        return !empty($configs['user_agent_suffix']) ? $userAgent . '/' . $configs['user_agent_suffix'] : $userAgent;
    }

    /**
     * Generate a random machine_id.
     */
    private function generateMachineId(): string
    {
        $prefix = !empty($this->configs['machine_id_prefix']) ? $this->configs['machine_id_prefix'] : '';

        return $prefix . $this->generateRandomString(self::MACHINE_ID_LENGTH - strlen($prefix));
    }

    /**
     * Generate a random password.
     */
    private function generatePassword(): string
    {
        return $this->generateRandomString(self::PASSWORD_LENGTH);
    }

    /**
     * Generate a  cryptographically secure random string.
     *
     * @throws Exception
     */
    private function generateRandomString(int $length): string
    {
        if ($length < 1) {
            throw new Exception('Length must be greater than zero.');
        }
        $chars = self::CREDENTIAL_CHARS;
        $chLen = strlen($chars);
        $res = '';
        for ($i = 0; $i < $length; ++$i) {
            $res .= $chars[random_int(0, $chLen - 1)];
        }

        return $res;
    }

    /**
     * Make a request and manage retry attempts (login and register errors).
     *
     * @param string $method
     * @param string $endpoint
     * @param array $parameters
     * @param array $scenarios
     * @return array
     * @throws ClientException
     */
    private function manageRequest(
        string $method,
        string $endpoint,
        array $parameters = [],
        array $scenarios = []
    ): array {
        $this->ensureAuth($scenarios);
        $loginRetry = 0;
        $lastMessage = '';
        $response = [];
        do {
            try {
                $retry = false;
                $headers = array_merge($this->headers, $this->handleTokenHeader());
                $response = $this->request($method, $endpoint, $parameters, $headers);
            } catch (ClientException $e) {
                if(401 !== $e->getCode()){
                    throw new ClientException($e->getMessage(), $e->getCode());
                }
                ++$loginRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
                $this->refreshToken($scenarios);
            }
        } while ($retry && ($loginRetry <= self::LOGIN_RETRY));
        if ($loginRetry > self::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
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
        if(!$this->token){
            throw new ClientException('Token is required.', 401);
        }
        return ['Authorization' => sprintf('Bearer %s', $this->token)];
    }

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     */
    private function login(array $scenarios = []): array
    {
        return $this->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [
                'password' => $this->password,
                'machine_id' => $this->machineId,
                'scenarios' => $scenarios, ],
            $this->headers
        );
    }

    /**
     * Generate and store new machine_id/password pair.
     */
    private function refreshCredentials(): void
    {
        $this->machineId = $this->generateMachineId();
        $this->password = $this->generatePassword();
        $this->storage->storeMachineId($this->machineId);
        $this->storage->storePassword($this->password);
    }

    /**
     * Retrieve a fresh token from login.
     *
     * @throws ClientException
     */
    private function refreshToken(array $scenarios = []): void
    {
        $loginResponse = $this->login($scenarios);

        $this->token = $loginResponse['token'] ?? null;
        if (!$this->token) {
            throw new ClientException('Token is required.', 401);
        }
        $this->storage->storeToken($this->token);
    }

    /**
     * Process a register call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers
     *
     * @throws ClientException
     */
    private function register(): void
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
                        'machine_id' => $this->machineId, ],
                    $this->headers
                );
            } catch (ClientException $e) {
                ++$registerRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
                if (500 === $e->getCode()) {
                    $this->refreshCredentials();
                }
            }
        } while ($retry && ($registerRetry <= self::REGISTER_RETRY));
        if ($registerRetry > self::REGISTER_RETRY) {
            $message = "Could not register after $registerRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
        }
    }

    /**
     * Check if we should refresh machine_id/password pair.
     */
    private function shouldRefreshCredentials(): bool
    {
        if (!$this->machineId || !$this->password) {
            return true;
        }
        $prefix = !empty($this->configs['machine_id_prefix']) ? $this->configs['machine_id_prefix'] : null;
        // Verify that current machine_id starts with configured prefix
        if ($prefix) {
            return 0 !== substr_compare($this->machineId, $prefix, 0, strlen($prefix));
        }

        return false;
    }
}
