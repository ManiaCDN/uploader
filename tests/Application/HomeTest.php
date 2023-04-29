<?php

namespace App\Tests\Application;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeTest extends WebTestCase {

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    public function setUp(): void {
        $this->client = static::createClient();
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        parent::setUp();
    }

    public function testHomeDefaultText(): void {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'No welcome set yet!');
    }

    public function testHomeWithText(): void {
        $testMessage = "# test message";

        $setting = new Setting();
        $setting->setName('welcome_message');
        $setting->setValue('# ' . $testMessage);
        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $testMessage);
    }
}
