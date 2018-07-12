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
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ManiaplanetController extends Controller
{
    /**
     * Link to this controller to start the "connect" process
     * It redirects to the Maniaplanet login page
     */
    public function connectAction()
    {
        // will redirect to Maniaplanet!
        return $this->get('oauth2.registry')
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
