<?php

namespace App\Tests\Integration;

class FileBrowsingTest extends IntegrationTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->givenLoggedInTestuser();
        $this->givenUploadedFiles([
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
        ]);
    }

    public function testUserCanSeeTheirFiles() {
        $this->client->request('GET', '/browse', ["path" => "/testuser"]);
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'file1.txt');
        $this->assertSelectorTextContains('body', 'file2.jpg');
        $this->assertSelectorTextContains('body', 'subfolder');
        $this->assertSelectorTextNotContains('body', 'otherfile.txt');
    }
}
