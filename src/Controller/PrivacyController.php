<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PrivacyController extends Controller
{
    public function show()
    {
        return $this->render('privacy/index.html.twig', [
            'controller_name' => 'UploadController',
        ]);
    }
}
