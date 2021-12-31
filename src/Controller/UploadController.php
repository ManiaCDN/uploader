<?php

namespace App\Controller;

use App\Entity\Path;
use App\Service\BlockedFilesManager;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class UploadController extends AbstractController
{

    private $storage;
    private $bfm;
    private $security;
    private $authChecker;

    /**
     * @param FilesystemOperator $masterStorage named-auto-wiring to match 'master.storage' from flysystem.yaml
     */
    public function __construct(
        FilesystemOperator $masterStorage,
        BlockedFilesManager $blockedFilesManager,
        Security $security,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->storage = $masterStorage;
        $this->bfm = $blockedFilesManager;
        $this->security = $security;
        $this->authChecker = $authChecker;
    }

    /**
     * Endpoint for receiving a file on the field 'file' and saving it to folder
     * determined by the field 'path'.
     * This includes security checks and error handling.
     */
    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if ($file->isValid()) {
            $pathToDir = new Path();
            $pathToDir->fromString($request->get('path'));
            $pathWithFileName = $pathToDir->append($file->getClientOriginalName());

            $this->onValidate($pathWithFileName);

            $stream = fopen($file->getRealPath(), 'r+');
            try {
                $this->storage->writeStream($pathWithFileName->getString(), $stream);
            } catch (FilesystemException $e) {
                return $this->json(['error' => $e->getMessage()], 500);
            } finally {
                fclose($stream);
            }

            $this->onUploadSuccess($pathWithFileName);

            return new Response();
        } else {
            return $this->json(['error' => $file->getErrorMessage()], 500);
        }
    }

    public function onValidate(Path $pathWithFileName): void
    {
        $this->ensureUserLoggedIn();
        $this->ensureUploadToDirectoryAllowed($this->security->getUser(), $pathWithFileName);
    }

    private function onUploadSuccess(Path $pathWithFileName) {
        $pathWithFileName->setBlocked(true);
        $this->bfm->block([$pathWithFileName]);
    }

    private function ensureUserLoggedIn(): void {
        if (!$this->authChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Only logged in users allowed to upload!');
        }
    }

    private function ensureUploadToDirectoryAllowed(?UserInterface $loggedInUser, Path $pathWithFileName): void {
        if (!$pathWithFileName->isWritableBy($loggedInUser)) {
            throw new ValidationException('Uploading outside user\'s directory is not allowed.');
        }
    }
}
