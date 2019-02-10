<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SettingsController extends AbstractController
{
    private $request;
    private $em;
    private $userRepository;
    private $authChecker;
    
    public function __construct(
            SettingRepository $settingRepository,
            AuthorizationCheckerInterface $authChecker
    ) {
        $this->userRepository = $settingRepository;
        $this->authChecker = $authChecker;
        $this->request = Request::createFromGlobals();
    }
    
    public function show()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        $this->request = Request::createFromGlobals();
        $this->em = $this->getDoctrine()->getManager();
        
        $this->setWelcome();
        
        $welcome_prev = $this->getDoctrine()
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
        
        $em = $this->getDoctrine()->getManager();
        
        // get current setting so we know if it needs to be updated
        $db_setting = $this->getDoctrine()
            ->getRepository(Setting::class)
            ->findOneBy(['name' => 'welcome_message']);
        
        if (null !== $db_setting) {
            // setting already exists, so let's update it
            $db_setting->setValue($value);
            $em->flush();
        }
        else {
            // setting doesn't exist yet
            $setting = new Setting();
            $setting->setName('welcome_message');
            $setting->setValue($value);

            $em->persist($setting);
            $em->flush();
        }
    }
}
