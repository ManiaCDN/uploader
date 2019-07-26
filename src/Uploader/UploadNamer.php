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
        
        /*
         * IMPORTANT!
         * For some reason that's not clear to me (maybe because this
         * service is public?) path is an existing object and NOT freshly
         * instantiated. It is exactly the one that gets instantiated
         * in the UploadValidationListener.
         */
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
        $raw_path = trim($request->get('path'), '/');
        
        $filename = $file->getClientOriginalName();
        
        //$this->path->setAlphanum(true);
        //$this->path->fromString($rawpath);
        $fullpath = $this->path->append($filename);
        
        return $fullpath->getString();
    }
}