<?php
/**
 * Make sure no one and no software bugs break
 * anything else in the filesystem.
 * Responsible for security related checks.
 */

namespace App\EventListener;

use App\Service\Security;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class UploadValidationListener
{
    private $tokenStorage;
    private $security;
    private $authChecker;
    
    public function __construct(
            TokenStorage $tokenStorage,
            Security $security,
            AuthorizationCheckerInterface $authChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->security = $security;
        $this->authChecker = $authChecker;
    }
    
    public function onValidate(ValidationEvent $event)
    {
        if (!$this->authChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Only logged in users allowed to upload!');
        }
        
        $login = $this->tokenStorage->getToken()->getUser()->getUsername();
        $request = $event->getRequest();
        $raw_path = $request->get('path');
        
        $this->checkPath($raw_path, $login);
    }
    
    /**
     * Checks:
     * 1) if user tries to use ../
     * 2) if the path from the request starts with the login
     * 
     * @param string $raw_path
     * @param string $login
     * @throws ValidationException
     * @return boolean
     */
    private function checkPath(string $path, string $login)
    {
        // step 1)
        if (false === $this->security->checkDirUp($path, false)) {
            throw new ValidationException('Usage of ../ not allowed! Please report this bug or stop cracking.');
        }
        
        //step 2)
        if ($login != $this->security->pathLogin($path)) {
            throw new ValidationException('Uploading outside user\'s directory is not allowed.');
        }
    }
}