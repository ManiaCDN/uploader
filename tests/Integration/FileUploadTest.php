<?php

namespace App\Tests\Integration;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->givenLoggedInTestuser();
    }

    public function testSuccessfulFileUpload()
    {
        $sourceFilePath = vfsStream::newFile('test.txt')
            ->withContent('Test file content for upload')
            ->at($this->vfsRoot)
            ->url();

        // Create UploadedFile with vfsStream source
        $uploadedFile = new UploadedFile(
            $sourceFilePath,
            'test.txt',
            'text/plain',
            null,
            UPLOAD_ERR_OK,
            true
        );

        $this->client->request('POST', '/_uploader/browse/upload', ['path' => '/testuser'], ['file' => $uploadedFile]);

        // Check response
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        // Verify file was stored in the correct location
        $expectedFilePath = "$this->uploadDir/testuser/test.txt";
        $this->assertTrue(file_exists($expectedFilePath), 'File should exist at the expected path');
        $this->assertEquals('Test file content for upload', file_get_contents($expectedFilePath));

        // Verify file was added to blocked files list
        $blockedFilesContent = file_get_contents($this->blockedFilesPath);
        $this->assertStringContainsString('testuser/test.txt', $blockedFilesContent, 'File should be in blocked files list');

        $this->client->request('GET', '/browse', ["path" => "/testuser"]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'test.txt');
    }
}
