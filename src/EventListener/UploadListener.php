<?php
namespace App\EventListener;

use App\Entity\Path;
use App\Service\BlockedFilesManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;

class UploadListener
{
    private $bfm;

    public function __construct(BlockedFilesManager $bfm)
    {
        $this->bfm = $bfm;
    }
    
    /**
     * Event to execute external actions
     * AFTER it has already been checked by
     * UploadValidationListener
     * 
     * @param PostPersistEvent $event
     * @return ResponseInterface
     */
    public function onUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();
        $file    = $event->getFile();

        $path = new Path();
        $path->fromString($request->get('path'));
        $path = $path->append($file->getBasename());
        $path->setBlocked(true);
        
        // block the file from public access
        $this->bfm->block([$path]);
        
        //if everything went fine
        $response = $event->getResponse();
        $response['success'] = true;
        return $response;
    }
}
