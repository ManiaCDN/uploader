<?php
namespace App\EventListener;

use App\Service\BlockedFilesManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;

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
     * @return array
     */
    public function onUpload(PostPersistEvent $event)
    {
        $request = $event->getRequest();
        $file    = $event->getFile();
        
        $pathname = sprintf('%s/%s',
            $request->get('path'),
            $file->getBasename()
        );
        
        // block the file from public access
        // block() expects and array and second param
        // indicates whether an unblock mail should be send
        $this->bfm->block([
            $pathname => true
        ], false);
        
        //if everything went fine
        $response = $event->getResponse();
        $response['success'] = true;
        return $response;
    }
}