<?php
/**
 * ManiaplanetController.php
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ManiaplanetController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     * It redirects to the Maniaplanet login page
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // will redirect to Maniaplanet!
        return $clientRegistry
            ->getClient('maniaplanet') // key used in config.yml
            ->redirect();
    }

    /**
     * After going to Maniaplanet, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config.yml
     */
    public function finish(Request $request) {
        return $this->render('maniaplanet/finish.html.twig', array(
            'login_finished' => true,
        ));
    }
}
