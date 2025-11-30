<?php

namespace App\Tests\Service;

use App\Service\BlockedFilesManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BlockedFilesManagerTest extends KernelTestCase
{
    /** @var BlockedFilesManager $bfm */
    private $bfm;

    function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();

        $this->bfm = $container->get(BlockedFilesManager::class);
    }

    function testBlockedFilesPathIsRelativeToPublicDir() {
        $_ENV["BLOCKED_FILES_LIST"] = '../tests/Resources/dummy_blocked_files.txt';

        $this->assertReadsSuccessfully();
    }

    function testBlockedFilesPathIsStillRelativeToPublicDirWhenChangingWorkingDirectory() {
        $_ENV["BLOCKED_FILES_LIST"] = '../tests/Resources/dummy_blocked_files.txt';

        $previousCwd = getcwd();
        chdir(dirname($previousCwd));

        try {
            $this->assertReadsSuccessfully();
        } finally {
            chdir($previousCwd);
        }
    }

    function testAbsolutePathsAreTakenAsTheyAre() {
        $_ENV["BLOCKED_FILES_LIST"] = $this->getContainer()->getParameter("kernel.project_dir") . '/tests/Resources/dummy_blocked_files.txt';

        $this->assertReadsSuccessfully();
    }

    public function assertReadsSuccessfully(): void
    {
        self::assertArrayHasKey('some_file.txt', $this->bfm->read());
    }
}
