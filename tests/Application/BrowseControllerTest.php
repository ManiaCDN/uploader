<?php

namespace App\Tests\Application;

use App\Repository\ManiaplanetUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BrowseControllerTest extends WebTestCase {



    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    public function setUp(): void {
        $this->client = static::createClient();
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        parent::setUp();
    }

    public function test_browse_with_path(): void {
        $userRepository = $this->getContainer()->get(ManiaplanetUserRepository::class);
        $testUser = $userRepository->findAll()[0];
        $this->client->loginUser($testUser);

        $this->client->request('GET', '/browse?path=/testuser');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('', 'Screenshot_20230228_182509.png');
    }

}
