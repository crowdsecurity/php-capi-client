<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Storage;

use Exception;

/**
 * File storage. Should be used only for test or/and as an example of StorageInterface implementation.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileStorage implements StorageInterface
{
    public const MACHINE_ID_FILE = 'machine_id.json';

    public const PASSWORD_FILE = 'password.json';

    public const TOKEN_FILE = 'token.json';

    /**
     * @var string $rootDir
     */
    private $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir = __DIR__)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveMachineId(): ?string
    {
        $storageContent = $this->readFile($this->rootDir . '/' . self::MACHINE_ID_FILE);

        return !empty($storageContent['machine_id']) ? $storageContent['machine_id'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function retrievePassword(): ?string
    {
        $storageContent = $this->readFile($this->rootDir . '/' . self::PASSWORD_FILE);

        return !empty($storageContent['password']) ? $storageContent['password'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveToken(): ?string
    {
        $storageContent = $this->readFile($this->rootDir . '/' . self::TOKEN_FILE);

        return !empty($storageContent['token']) ? $storageContent['token'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function storeMachineId(string $machineId): bool
    {
        try {
            $json = '{"machine_id":"' . $machineId . '"}';
            $this->writeFile($this->rootDir . '/' . self::MACHINE_ID_FILE, $json);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function storePassword(string $password): bool
    {
        try {
            $json = '{"password":"' . $password . '"}';
            $this->writeFile($this->rootDir . '/' . self::PASSWORD_FILE, $json);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function storeToken(string $token): bool
    {
        try {
            $json = '{"token":"' . $token . '"}';
            $this->writeFile($this->rootDir . '/' . self::TOKEN_FILE, $json);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Read the content of some file
     *
     * @param string $file
     * @return array
     */
    private function readFile(string $file): array
    {
        $result = [];
        $string = @file_get_contents($file);
        if (false === $string) {
            return $result;
        }
        $json = json_decode($string, true);
        if (null === $json) {
            return $result;
        }

        return $json;
    }

    /**
     * Write some content in a file
     *
     * @param string $filepath
     * @param string $content
     * @return void
     */
    private function writeFile(string $filepath, string $content): void
    {
        $file = fopen($filepath, 'w');
        fwrite($file, $content);
        fclose($file);
    }
}
