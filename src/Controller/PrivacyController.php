<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PrivacyController extends AbstractController
{
    public function show()
    {
        return $this->render('privacy/index.html.twig', [
            'controller_name' => 'UploadController',
        ]);
    }
}
