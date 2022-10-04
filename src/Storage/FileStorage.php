<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Storage;

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
    private const MACHINE_ID_FILE = __DIR__ . '/machine_id.json';

    private const PASSWORD_FILE = __DIR__ . '/password.json';

    private const TOKEN_FILE = __DIR__ . '/token.json';

    /**
     * {@inheritdoc}
     */
    public function retrieveMachineId(): ?string
    {
        $storageContent = $this->readFile(self::MACHINE_ID_FILE);

        return !empty($storageContent['machine_id']) ? $storageContent['machine_id'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function retrievePassword(): ?string
    {
        $storageContent = $this->readFile(self::PASSWORD_FILE);

        return !empty($storageContent['password']) ? $storageContent['password'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveToken(): ?string
    {
        $storageContent = $this->readFile(self::TOKEN_FILE);

        return !empty($storageContent['token']) ? $storageContent['token'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function storeMachineId(string $machineId): bool
    {
        try {
            $json = '{"machine_id":"' . $machineId . '"}';
            $this->writeFile(self::MACHINE_ID_FILE, $json);
        } catch (\Exception $e) {
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
            $this->writeFile(self::PASSWORD_FILE, $json);
        } catch (\Exception $e) {
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
            $this->writeFile(self::TOKEN_FILE, $json);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
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

    private function writeFile(string $filepath, string $content): void
    {
        $file = fopen($filepath, 'w');
        fwrite($file, $content);
        fclose($file);
    }
}
