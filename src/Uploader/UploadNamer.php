<?php

namespace App\Uploader;

use App\Entity\Path;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UploadNamer implements NamerInterface
{
    private $requestStack;
    
    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
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

        $path = new Path();
        $path->fromString($raw_path);
        $fullpath = $path->append($filename);
        
        return $fullpath->getString();
    }
}
