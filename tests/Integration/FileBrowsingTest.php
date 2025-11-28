<?php

namespace App\Tests\Integration;

use App\Entity\ManiaplanetUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpKernel\KernelInterface;

class FileBrowsingTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    private function initDatabase(): void
    {
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->updateSchema($metaData);
    }
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->initDatabase();
        
        // Setup virtual filesystem with test files
        vfsStream::setup('root', null, [
            'uploads' => [
                'testuser' => [
                    'file1.txt' => 'Test file content',
                    'file2.jpg' => 'fake image content',
                    'subfolder' => [
                        'nested.txt' => 'Nested file content'
                    ]
                ],
                'otheruser' => [
                    'otherfile.txt' => 'Other user content'
                ]
            ]
        ]);
        
        // Override environment variables
        $_ENV['UPLOAD_DIR'] = vfsStream::url('root/uploads');
        $_ENV['PUBLIC_UPLOAD_URL'] = 'https://cdn.example.com';
    }
    
    public function testUserCanSeeTheirFiles()
    {
        // 1. Create test user
        $testUser = $this->createTestUser('testuser', 'test@example.com');
        
        // 2. Create client and authenticate user
        $this->client->loginUser($testUser);
        
        // 3. Make request to browse user's files
        $this->client->request('GET', '/browse', ["path" => "/testuser"]);
        
        // 4. Assert response is successful
        $this->assertResponseIsSuccessful();
        
        // 5. Assert user's files are shown
        $this->assertSelectorTextContains('body', 'file1.txt');
        $this->assertSelectorTextContains('body', 'file2.jpg');
        $this->assertSelectorTextContains('body', 'subfolder');
        
        // 6. Assert other user's files are NOT shown
        $this->assertSelectorTextNotContains('body', 'otherfile.txt');
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
}
