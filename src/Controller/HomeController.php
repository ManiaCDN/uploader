<?php
/**
 * Home page
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Controller;

use App\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function show()
    {
        $welcome = $this->getDoctrine()
            ->getRepository(Setting::class)
            ->findOneBy(['name' => 'welcome_message'])
            ->getValue();
        
        return $this->render('home/index.html.twig', 
                [
                    "message" => $welcome
                ]);
    }
}
