<?php
namespace App\EventListener;

use App\Service\Path;
use App\Service\BlockedFilesManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;

class UploadListener
{
    private $bfm;
    private $path;

    public function __construct(BlockedFilesManager $bfm, Path $path)
    {
        $this->bfm = $bfm;
        $this->path = $path;
    }
    
    /**
     * Event to execute external actions
     * AFTER it has already been checked by
     * UploadValidationListener
     * 
     * @param PostPersistEvent $event
     * @return array
     */
    public function onUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();
        $file    = $event->getFile();
        
        $this->path->fromString($request->get('path'));
        $this->path = $this->path->append($file->getBasename());
        $this->path->setBlocked(true);
        
        // block the file from public access
        $this->bfm->block([$this->path]);
        
        //if everything went fine
        $response = $event->getResponse();
        $response['success'] = true;
        return $response;
    }
}