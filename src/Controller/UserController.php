<?php

namespace App\Controller;

use App\Entity\ManiaplanetUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserController extends AbstractController
{
    private $request;
    private $em;
    private $requestStack;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->request = Request::createFromGlobals();
        $this->em = $entityManager;
        $this->requestStack = $requestStack;
    }
    
    public function show()
    {
        return $this->render('user/index.html.twig', [
            
        ]);
    }
    
    public function setNotificationSettings() {
        $email = $this->request->query->get('email', null);
        if ($email === null) {
            die('No email provided under GET parameter "email"');
        }
        
        // find user by email
        $existingUser = $this->em->getRepository(ManiaplanetUser::class)
            ->findOneBy(['email' => $email]);
        
        // annouce changes to doctrine
        $send_approval_notification = $this->request->query->get('email_send_approval_notification', false);
        if ($send_approval_notification !== null) {
            $existingUser->setEmailSendApprovalNotification((bool) $send_approval_notification);
        }
        
        // save changes in db
        $this->em->flush();
        
        $this->requestStack->getSession()->getFlashBag()->
            add('success', 'Changes saved successfully.');
        
        if ($this->request->query->get('redirect', null) == 'homepage') {
            return $this->redirectToRoute('homepage');
        } else {
            return $this->redirectToRoute('user');
        }
    }
}
