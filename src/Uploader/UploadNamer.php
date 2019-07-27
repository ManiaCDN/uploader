<?php

namespace App\Uploader;

use App\Service\Path;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UploadNamer implements NamerInterface
{
    private $requestStack;
    private $path;
    
    public function __construct(RequestStack $requestStack, Path $path) {
        $this->requestStack = $requestStack;
        $this->path = $path;
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
        
        $filename = $file->getClientOriginalName();
        
        $this->path->fromString($raw_path);
        $fullpath = $this->path->append($filename);
        
        return $fullpath->getString();
    }
}