<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SettingsController extends AbstractController
{
    private $request;
    private $managerRegistry;
    private $authChecker;
    
    public function __construct(
            AuthorizationCheckerInterface $authChecker,
            ManagerRegistry $managerRegistry
    ) {
        $this->authChecker = $authChecker;
        $this->request = Request::createFromGlobals();
        $this->managerRegistry = $managerRegistry;
    }
    
    public function show()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        $this->request = Request::createFromGlobals();
        
        $this->setWelcome();
        
        $welcome_prev = $this->managerRegistry
            ->getRepository(Setting::class)
            ->getWelcome();
        
        return $this->render('admin/settings/index.html.twig', [
            'welcome_message' => $welcome_prev,
        ]);
    }
    
    private function csrfCheck() {
        $token = $this->request->request->get('token');
        
        if (!$this->isCsrfTokenValid('admin_edit-home', $token)) {
            throw new \Exception('CSRF token invalid!');
        }
    }
     
    private function setWelcome() {
        $value = $this->request->request->get('welcome_message');
        if (null === $value) {
            return; // user most likely just came from clicking on the navigation entry
        }
        
        $this->csrfCheck();
        
        // get current setting so we know if it needs to be updated
        $db_setting = $this->managerRegistry
            ->getRepository(Setting::class)
            ->findOneBy(['name' => 'welcome_message']);
        
        if (null !== $db_setting) {
            // setting already exists, so let's update it
            $db_setting->setValue($value);
            $this->managerRegistry->getManager()->flush();
        }
        else {
            // setting doesn't exist yet
            $setting = new Setting();
            $setting->setName('welcome_message');
            $setting->setValue($value);

            $this->managerRegistry->getManager()->persist($setting);
            $this->managerRegistry->getManager()->flush();
        }
    }
}
