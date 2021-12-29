<?php
/**
 * Make sure no one and no software bugs break
 * anything else in the filesystem.
 * Responsible for security related checks.
 */

namespace App\EventListener;

use App\Entity\Path;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class UploadValidationListener
{
    private $tokenStorage;
    private $authChecker;
    
    public function __construct(
            UsageTrackingTokenStorage $tokenStorage,
            AuthorizationCheckerInterface $authChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
    }
    
    /**
     * Checks if 
     * 1) User ist logged in
     * 2) Path doesn't escape the upload dir (checked automatically
     *    within the path class)
     * 3) The user is uploading within his own directory
     */
    public function onValidate(ValidationEvent $event)
    {
        if (!$this->authChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Only logged in users allowed to upload!');
        }
        
        $user = $this->tokenStorage->getToken()->getUser();
        $request = $event->getRequest();
        $raw_path = $request->get('path');
        
        $path = new Path();
        $path->fromString($raw_path); // check 2) happens inside here
        
        if (!$path->isWritableBy($user)) {
            throw new ValidationException('Uploading outside user\'s directory is not allowed.');
        }
    }
}
