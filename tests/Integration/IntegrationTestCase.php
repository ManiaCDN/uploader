<?php

namespace App\Tests\Integration;

use App\Entity\ManiaplanetUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IntegrationTestCase extends WebTestCase
{

    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;
    protected KernelBrowser $client;
    protected vfsStreamDirectory $vfsRoot;
    protected string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->initDatabase();

        $this->vfsRoot = vfsStream::setup('root', null, ['uploads' => []]);
        $this->uploadDir = vfsStream::url('root/uploads');
        $_ENV['UPLOAD_DIR'] = $this->uploadDir;

        $this->blockedFilesPath = vfsStream::newFile('blocked_files_test.txt')->at($this->vfsRoot)->url();
        $_ENV['BLOCKED_FILES_LIST'] = $this->blockedFilesPath;
    }

    protected function givenUploadedFiles(array $uploadedFiles)
    {
        vfsStream::create(['uploads' => $uploadedFiles], $this->vfsRoot);
    }

    protected function givenLoggedInTestuser() {
        $testUser = $this->createTestUser('testuser', 'test@example.com');
        $this->client->loginUser($testUser);
    }

    private function createTestUser(string $login, string $email): ManiaplanetUser
    {
        $user = new ManiaplanetUser();
        $user->setLogin($login);
        $user->setNickname($login);
        $user->setEmail($email);
        $user->setRole('ROLE_USER');
        $user->setEmailSendApprovalNotification(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function initDatabase(): void
    {
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->updateSchema($metaData);
    }

}
