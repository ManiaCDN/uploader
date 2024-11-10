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
use Doctrine\Persistence\ManagerRegistry;
use Michelf\Markdown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    private $mangerRegistry;

    public function __construct(ManagerRegistry $mangerRegistry) {
        $this->mangerRegistry = $mangerRegistry;
    }

    public function show()
    {
        $welcomeMessageMarkdown = $this->mangerRegistry
            ->getRepository(Setting::class)
            ->getWelcome();
        
        return $this->render('home/index.html.twig', 
                [
                    "message" => Markdown::defaultTransform($welcomeMessageMarkdown)
                ]);
    }
}
