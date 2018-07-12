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


class UploadValidationListener
{
    private $tokenStorage;
    private $security;
    
    public function __construct(TokenStorage $tokenStorage, Security $security)
    {
        $this->tokenStorage = $tokenStorage;
        $this->security = $security;
    }
    
    public function onValidate(ValidationEvent $event)
    {
        $login = $this->tokenStorage->getToken()->getUser()->getUsername();
        /*
        $config  = $event->getConfig();
        $file    = $event->getFile();
        $type    = $event->getType();
         */
        $request = $event->getRequest();
        
        $raw_path  = $request->get('path');
        
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
            
        /*
        if (strpos($path, '../')) {
            if ($throw_errors) {
                throw new ValidationException('Usage of ../ not allowed! Please report this bug or stop cracking.');
            }
            return false;
        }
         * 
         */
        
        //step 2)
        if ($login != $this->security->pathLogin($path)) {
            throw new ValidationException('Uploading outside user\'s directory is not allowed.');
        }
        
        /* REMOVE ME
        $path = trim($path, '/');
        
        $array = explode('/', $path);
        $dir_user = $array[0];
        if ($dir_user === $login) {
            unset($array[0]);
            return implode('/', $array);
        } else {
            if ($throw_errors) {
                throw new ValidationException('Uploading outside user\'s directory is not allowed: '.$dir_user);
            }
            return false;
        }
         * 
         */
    }
}