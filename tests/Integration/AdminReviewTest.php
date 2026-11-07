<?php

namespace App\Tests\Integration;

class AdminReviewTest extends IntegrationTestCase
{
    private const BLOCKED_FILE = 'testuser/file1.txt';
    private const UNBLOCKED_FILE = 'testuser/file2.txt';

    protected function setUp(): void
    {
        parent::setUp();

        $this->givenUploadedFiles([
            'testuser' => [
                'file1.txt' => 'Blocked file content',
                'file2.txt' => 'Unblocked file content',
            ],
        ]);

        // file1.txt awaits review, file2.txt is already approved
        file_put_contents($this->blockedFilesPath, self::BLOCKED_FILE . "\n");
    }

    public function testNonAdminCannotAccessReviewPage()
    {
        $this->givenLoggedInUser();

        // test env has no error controller, so the exception is rethrown
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);

        $this->client->request('GET', '/admin/review');
    }

    public function testAdminSeesOnlyBlockedFiles()
    {
        $this->givenLoggedInAdminuser();

        $this->client->request('GET', '/admin/review');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'file1.txt');
        $this->assertSelectorTextNotContains('body', 'file2.txt');
    }

    public function testApproveFileRemovesItFromBlockedListAndNotifiesUser()
    {
        $this->givenLoggedInAdminuser();
        $this->createTestUser(); // file owner, notifications enabled

        $this->client->request('GET', '/admin/review');
        $csrfToken = $this->getCsrfToken();

        $this->client->request('POST', '/admin/review', [
            'block' => [self::BLOCKED_FILE => 'false'],
            'token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString(
            self::BLOCKED_FILE,
            file_get_contents($this->blockedFilesPath),
            'Approved file should be removed from blocked files list'
        );
        $this->assertSelectorTextContains('body', 'User notification was successfully sent to testuser');
    }

    public function testRejectFileKeepsItBlocked()
    {
        $this->givenLoggedInAdminuser();

        $this->client->request('GET', '/admin/review');
        $csrfToken = $this->getCsrfToken();

        $this->client->request('POST', '/admin/review', [
            'block' => [self::BLOCKED_FILE => 'true'],
            'token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            self::BLOCKED_FILE,
            file_get_contents($this->blockedFilesPath),
            'Rejected file should stay in blocked files list'
        );
    }

    public function testDeleteFileRemovesItFromFilesystemAndBlockedList()
    {
        $this->givenLoggedInAdminuser();
        $this->createTestUser(); // file owner, notifications enabled

        $this->client->request('GET', '/admin/review');
        $csrfToken = $this->getCsrfToken();

        $this->client->request('POST', '/admin/review', [
            'block' => [self::BLOCKED_FILE => 'true'],
            'delete' => [self::BLOCKED_FILE => 'true'],
            'token' => $csrfToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertFileDoesNotExist(
            "$this->uploadDir/testuser/file1.txt",
            'Deleted file should be removed from the filesystem'
        );
        $this->assertStringNotContainsString(
            self::BLOCKED_FILE,
            file_get_contents($this->blockedFilesPath),
            'Deleted file should be removed from blocked files list'
        );
        $this->assertSelectorTextContains('body', 'file(s) were deleted');
    }

    public function testInvalidCsrfTokenIsRejected()
    {
        $this->givenLoggedInAdminuser();

        try {
            $this->client->request('POST', '/admin/review', [
                'block' => [self::BLOCKED_FILE => 'false'],
                'token' => 'invalid-token',
            ]);
            $this->fail('Expected an exception for the invalid CSRF token');
        } catch (\Exception $e) {
            $this->assertStringContainsString('CSRF token invalid', $e->getMessage());
        }

        $this->assertStringContainsString(
            self::BLOCKED_FILE,
            file_get_contents($this->blockedFilesPath),
            'File must stay blocked when CSRF validation fails'
        );
    }

    public function testAdminCanDownloadBlockedFile()
    {
        $this->givenLoggedInAdminuser();

        $this->client->request('GET', '/admin/review/download', ['file' => self::BLOCKED_FILE]);

        $this->assertResponseIsSuccessful();
        // BinaryFileResponse::getContent() is false, the file is sent separately
        $this->assertSame(
            'Blocked file content',
            file_get_contents($this->client->getResponse()->getFile()->getPathname())
        );
        $this->assertStringContainsString(
            'attachment',
            $this->client->getResponse()->headers->get('content-disposition')
        );
    }

    private function getCsrfToken(): string
    {
        return $this->client->getCrawler()
            ->filter('input[name="token"]')
            ->attr('value');
    }
}
