<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Storage;

/**
 * Storage interface.
 *
 * Must be used to store machine_id, password and token
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
interface StorageInterface
{
    public function retrieveMachineId(): ?string;

    public function retrievePassword(): ?string;

    public function retrieveToken(): ?string;

    public function storeMachineId(string $machineId): bool;

    public function storePassword(string $password): bool;

    public function storeToken(string $token): bool;
}
