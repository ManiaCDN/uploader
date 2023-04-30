<?php
namespace App\EventListener;

use App\Entity\Path;
use App\Service\BlockedFilesManager;
use App\Service\PathFactory;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Oneup\UploaderBundle\Uploader\Response\ResponseInterface;

class UploadListener
{
    private $bfm;

    private PathFactory $pathFactory;

    public function __construct(
        BlockedFilesManager $bfm,
        PathFactory $pathFactory
    ) {
        $this->bfm = $bfm;
        $this->pathFactory = $pathFactory;
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

        $path = $this->pathFactory->newInstance();
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
