<?php

namespace App\Uploader;

use App\Service\FilesystemManager;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\RequestStack;

class UploadNamer implements NamerInterface
{
    private $requestStack;
    private $fsm;
    
    public function __construct(RequestStack $requestStack, FilesystemManager $fsm) {
        $this->requestStack = $requestStack;
        $this->fsm = $fsm;
    }
    
    /**
     * Security is checked in UploadValidation listener
     *
     * @param FileInterface $file
     * @return string The directory name.
     */
    public function name(FileInterface $file)
    {
        $request = $this->requestStack->getCurrentRequest();
        $raw_path = $request->get('path');
        
        $path = trim($raw_path, '/');
        $filename = $this->fsm->cleanPath($file->getClientOriginalName()); // remove spaces and specialchars
        return sprintf('%s/%s',
            $path,
            $filename
        );
    }
}