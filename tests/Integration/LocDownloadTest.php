<?php

namespace App\Tests\Integration;

use App\Tests\Support\ZipReader;
use Webmozart\PathUtil\Path as PathUtil;

class LocDownloadTest extends IntegrationTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->givenLoggedInUser();
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

    public function testLocDownloadCreatesZipWithLocFiles()
    {
        $entries = $this->downloadLocs('testuser');

        ksort($entries);
        $this->assertEquals([
            'testuser/file1.txt.loc'      => $this->publicUrl('testuser/file1.txt'),
            'testuser/file2.jpg.loc'      => $this->publicUrl('testuser/file2.jpg'),
            'testuser/subfolder/nested.txt.loc' => $this->publicUrl('testuser/subfolder/nested.txt'),
        ], $entries);
    }

    public function testLocDownloadForSubdirectory()
    {
        $entries = $this->downloadLocs('testuser/subfolder');

        $this->assertEquals([
            'testuser/subfolder/nested.txt.loc' => $this->publicUrl('testuser/subfolder/nested.txt'),
        ], $entries);
    }

    public function testLocDownloadRequiresAuthentication()
    {
        $this->client->request('GET', '/logout');
        $this->client->request('GET', '/browse/locs_download', ['path' => 'testuser']);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/connect/maniaplanet', $response->headers->get('Location'));
    }

    /**
     * Requests the locs download and returns the extracted zip entries.
     */
    private function downloadLocs(string $path): array
    {
        // The streamed body is echoed when the kernel terminates, so capture
        // it with output buffering: getResponse()->getContent() is empty for
        // StreamedResponse.
        ob_start();
        try {
            $this->client->request('GET', '/browse/locs_download', ['path' => $path]);
        } finally {
            $zipContent = (string) ob_get_clean();
        }

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('locators.zip', $response->headers->get('Content-Disposition'));
        // Symfony's ResponseHeaderBag adds max-age=0/must-revalidate on top
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));

        // Diagnostic output (e.g. xdebug deprecation traces) may precede the
        // zip in the captured buffer, so cut everything before the first
        // local file header signature.
        $zipStart = strpos($zipContent, "PK\x03\x04");
        $this->assertNotFalse($zipStart, 'Streamed response should contain a zip archive');
        $zipContent = substr($zipContent, $zipStart);

        return ZipReader::extract($zipContent);
    }

    private function publicUrl(string $path): string
    {
        return PathUtil::join($_ENV['PUBLIC_UPLOAD_URL'], $path);
    }
}
