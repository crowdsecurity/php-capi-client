<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests\Unit;

/**
 * Test for file storage.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\CapiClient\Storage\FileStorage;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class FileStorageTest extends TestCase
{
    public const TMP_DIR = '/tmp';
    /**
     * @var  vfsStreamDirectory
     */
    private $root;

    /**
     * set up test environment
     */
    public function setUp(): void
    {
        $this->root = vfsStream::setup(self::TMP_DIR);
    }

    public function testRetrieveMachineId()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(FileStorage::MACHINE_ID_FILE, 0444)
            ->at($this->root)
            ->setContent('{"machine_id":"test-machine-id"}');
        $this->assertEquals(
            'test-machine-id',
            $storage->retrieveMachineId(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(FileStorage::MACHINE_ID_FILE, 0000)
            ->at($this->root)
            ->setContent('{"machine_id":"test-machine-id"}');
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(FileStorage::MACHINE_ID_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-machine-id"}');
        $this->assertEquals(
            null,
            $storage->retrieveMachineId(),
            'Should be null if bad content'
        );
    }

    public function testRetrievePassword()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(FileStorage::PASSWORD_FILE, 0444)
            ->at($this->root)
            ->setContent('{"password":"test-password"}');
        $this->assertEquals(
            'test-password',
            $storage->retrievePassword(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(FileStorage::PASSWORD_FILE, 0000)
            ->at($this->root)
            ->setContent('{"password":"test-password"}');
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(FileStorage::PASSWORD_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-password"}');
        $this->assertEquals(
            null,
            $storage->retrievePassword(),
            'Should be null if bad content'
        );
    }

    public function testRetrieveToken()
    {
        $storage = new FileStorage($this->root->url());
        // Test no file
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if no file on file system'
        );
        // test file ok
        vfsStream::newFile(FileStorage::TOKEN_FILE, 0444)
            ->at($this->root)
            ->setContent('{"token":"test-token"}');
        $this->assertEquals(
            'test-token',
            $storage->retrieveToken(),
            'Should be ok if file is present with right content and permission'
        );
        // Test file not readable
        vfsStream::newFile(FileStorage::TOKEN_FILE, 0000)
            ->at($this->root)
            ->setContent('{"token":"test-token"}');
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if not readable'
        );
        // Test file bad content
        vfsStream::newFile(FileStorage::TOKEN_FILE, 0000)
            ->at($this->root)
            ->setContent('{"foo":"test-token"}');
        $this->assertEquals(
            null,
            $storage->retrieveToken(),
            'Should be null if bad content'
        );
    }

    public function testStoreMachineId()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . FileStorage::MACHINE_ID_FILE),
            'File should not exist'
        );

        $storage->storeMachineId('test-machine-id');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . FileStorage::MACHINE_ID_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"machine_id":"test-machine-id"}',
            file_get_contents($this->root->url() . '/' . FileStorage::MACHINE_ID_FILE),
            'Should have right content'
        );
    }

    public function testStorePassword()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . FileStorage::PASSWORD_FILE),
            'File should not exist'
        );

        $storage->storePassword('test-pwd');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . FileStorage::PASSWORD_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"password":"test-pwd"}',
            file_get_contents($this->root->url() . '/' . FileStorage::PASSWORD_FILE),
            'Should have right content'
        );
    }

    public function testStoreToken()
    {
        $storage = new FileStorage($this->root->url());

        $this->assertEquals(
            false,
            file_exists($this->root->url() . '/' . FileStorage::TOKEN_FILE),
            'File should not exist'
        );

        $storage->storeToken('test-token');

        $this->assertEquals(
            true,
            file_exists($this->root->url() . '/' . FileStorage::TOKEN_FILE),
            'Should create file'
        );

        $this->assertEquals(
            '{"token":"test-token"}',
            file_get_contents($this->root->url() . '/' . FileStorage::TOKEN_FILE),
            'Should have right content'
        );
    }
}
