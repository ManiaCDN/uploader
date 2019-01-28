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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function show()
    {
        return $this->render('home/index.html.twig', 
                [
                    "message" => "<h2>Welcome to the ManiaCDN Uploader v2</h2>"
                ]);
    }
}
