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
    public const ENROLL_ENDPOINT = '/watchers/enroll';
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
     * @var array
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
        $this->configs['api_url'] =
            Constants::ENV_PROD === $this->getConfig('env') ? Constants::URL_PROD : Constants::URL_DEV;
        parent::__construct($this->configs, $requestHandler);
    }

    /**
     * Process an enroll call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_enroll
     */
    public function enroll(string $name, bool $overwrite, string $enrollKey, array $tags = []): array
    {
        $params = [
            'name' => $name,
            'overwrite' => $overwrite,
            'attachment_key' => $enrollKey,
            'tags' => $tags,
        ];

        return $this->manageRequest('POST', self::ENROLL_ENDPOINT, $params);
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     */
    public function getStreamDecisions(): array
    {
        return $this->manageRequest('GET', self::DECISIONS_STREAM_ENDPOINT, []);
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     */
    public function pushSignals(array $signals): array
    {
        return $this->manageRequest('POST', self::SIGNALS_ENDPOINT, $signals);
    }

    /**
     * Check if two indexed arrays are equals.
     */
    private function areEquals(array $arrayOne, array $arrayTwo): bool
    {
        $countOne = count($arrayOne);

        return $countOne === count($arrayTwo) && $countOne === count(array_intersect($arrayOne, $arrayTwo));
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configs]);
    }

    /**
     * Ensure that machine is registered and that we have a token.
     */
    private function ensureAuth(): void
    {
        $this->ensureRegister();
        $this->token = $this->storage->retrieveToken();
        if ($this->shouldLogin()) {
            $this->handleLogin();
        }
    }

    /**
     * Ensure that machine credentials are ready tu use.
     */
    private function ensureRegister(): void
    {
        $this->machineId = $this->storage->retrieveMachineId();
        $this->password = $this->storage->retrievePassword();
        if ($this->shouldRefreshCredentials($this->machineId, $this->password, $this->configs)) {
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
    private function generateMachineId(array $configs = []): string
    {
        $prefix = !empty($configs['machine_id_prefix']) ? $configs['machine_id_prefix'] : '';

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
     * Generate a cryptographically secure random string.
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
     * Retrieve a fresh token from login.
     *
     * @throws ClientException
     */
    private function handleLogin(): void
    {
        $loginResponse = $this->login();

        $this->token = $loginResponse['token'] ?? null;
        if (!$this->token) {
            throw new ClientException('Login response does not contain required token.', 401);
        }
        $this->storage->storeToken($this->token);
        $configScenarios = $this->getConfig('scenarios');
        $this->storage->storeScenarios($configScenarios ?: []);
    }

    /**
     * Handle required token (JWT) in header for next CAPI calls.
     *
     * @throws ClientException
     */
    private function handleTokenHeader(): array
    {
        if (!$this->token) {
            throw new ClientException('Token is required.', 401);
        }

        return ['Authorization' => sprintf('Bearer %s', $this->token)];
    }

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     */
    private function login(): array
    {
        return $this->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [
                'password' => $this->password,
                'machine_id' => $this->machineId,
                'scenarios' => $this->getConfig('scenarios'), ],
            $this->headers
        );
    }

    /**
     * Make a request and manage retry attempts (login and register errors).
     *
     * @throws ClientException
     */
    private function manageRequest(
        string $method,
        string $endpoint,
        array $parameters = []
    ): array {
        $this->ensureAuth();
        $loginRetry = 0;
        $lastMessage = '';
        $response = [];
        $retry = false;
        do {
            try {
                if ($retry) {
                    $retry = false;
                    $this->handleLogin();
                }
                $headers = array_merge($this->headers, $this->handleTokenHeader());
                $response = $this->request($method, $endpoint, $parameters, $headers);
            } catch (ClientException $e) {
                if (401 !== $e->getCode()) {
                    throw new ClientException($e->getMessage(), $e->getCode());
                }
                ++$loginRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($loginRetry <= self::LOGIN_RETRY));
        if ($loginRetry > self::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
        }

        return $response;
    }

    /**
     * Generate and store new machine_id/password pair.
     */
    private function refreshCredentials(): void
    {
        $this->machineId = $this->generateMachineId($this->configs);
        $this->password = $this->generatePassword();
        $this->storage->storeMachineId($this->machineId);
        $this->storage->storePassword($this->password);
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
        $retry = false;
        do {
            try {
                if ($retry) {
                    $retry = false;
                    $this->refreshCredentials();
                }
                $this->request(
                    'POST',
                    self::REGISTER_ENDPOINT,
                    [
                        'password' => $this->password,
                        'machine_id' => $this->machineId, ],
                    $this->headers
                );
            } catch (ClientException $e) {
                if (500 !== $e->getCode()) {
                    throw new ClientException($e->getMessage(), $e->getCode());
                }
                ++$registerRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($registerRetry <= self::REGISTER_RETRY));
        if ($registerRetry > self::REGISTER_RETRY) {
            $message = "Could not register after $registerRetry attempts. Last error was: ";
            throw new ClientException($message . $lastMessage);
        }
    }

    /**
     * Check if we should log in (handle token and scenarios).
     */
    private function shouldLogin(): bool
    {
        if (!$this->token) {
            return true;
        }

        // Verify that we have stored scenarios and that the match with current scenarios
        $storedScenarios = $this->storage->retrieveScenarios();
        if (!$storedScenarios) {
            return true;
        }
        $configScenarios = $this->getConfig('scenarios');

        return !$this->areEquals($storedScenarios, $configScenarios ?: []);
    }

    /**
     * Check if we should refresh machine_id/password pair.
     */
    private function shouldRefreshCredentials(?string $machineId, ?string $password, array $configs): bool
    {
        if (!$machineId || !$password) {
            return true;
        }
        $prefix = !empty($configs['machine_id_prefix']) ? $configs['machine_id_prefix'] : null;
        // Verify that current machine_id starts with configured prefix
        if ($prefix) {
            return 0 !== substr_compare($machineId, $prefix, 0, strlen($prefix));
        }

        return false;
    }
}
