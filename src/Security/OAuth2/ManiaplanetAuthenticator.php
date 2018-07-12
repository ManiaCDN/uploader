<?php
/**
 * ManiaplanetAuthenticator.php
 * 
 * @author     Tom Valk <tomvalk@lt-box.info>
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2017 Tom Valk, 2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */

namespace App\Security\OAuth2;

use App\Entity\ManiaplanetUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;


class ManiaplanetAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $router;
    private $flashBag;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, FlashBagInterface $flashBag)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $entityManager;
        $this->router = $router;
        $this->flashBag = $flashBag;
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        // return $request->attributes->get('_route') === 'connect_facebook_check';
        //return $request->headers->has('X-AUTH-TOKEN'); // possibly incorrect
        
        if ($request->getPathInfo() == '/connect/maniaplanet/finish') {
            return true;
        }
    }
    
    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('connect_maniaplanet'));
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * For example, for a form login, you might:
     *
     *      if ($request->request->has('_username')) {
     *          return array(
     *              'username' => $request->request->get('_username'),
     *              'password' => $request->request->get('_password'),
     *          );
     *      } else {
     *          return;
     *      }
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
     *
     * @param Request $request
     *
     * @return mixed|null
     */
    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/connect/maniaplanet/finish') {
            // Don't auth.
            return;
        }
        
        return $this->fetchAccessToken($this->getManiaplanetClient());
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @throws AuthenticationException
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** 
         * From OAuth API
         * @var ManiaplanetResourceOwner $maniaplanetUser
         */
        $maniaplanetUser = $this->getManiaplanetClient()->fetchUserFromToken($credentials);

        $login = $maniaplanetUser->getLogin();

        // Try to find existing user in database
        // $existingUser = $this->em->getRepository(ManiaplanetUser::class) should also work
        $existingUser = $this->em->getRepository('App:ManiaplanetUser')
            ->findOneBy(['login' => $login]);

        if ($existingUser) {
            if ($existingUser->updateFromManiaplanetUser($maniaplanetUser)) {
                // updateFromManiaplanetUser() updates the properties of its object using $maniaplanetUser
                // if some properties have changed (email, nickname ...), updateFromManiaplanetUser() returns true
                // -> changes are written into database
                $this->em->persist($existingUser);
                $this->em->flush();
            }
            return $existingUser;
        }

        // If not, register user by creating a new object for it and setting data from OAuth API
        $user = new ManiaplanetUser();
        $user->setEmail($maniaplanetUser->getEmail());
        $user->setNickname($maniaplanetUser->getNickname());
        $user->setLogin($maniaplanetUser->getLogin());
        $user->setRole(['ROLE_USER']);

        $this->em->persist($user); // tell doctrine about the change
        $this->em->flush(); // let doctrine execute it and run the query to the database

        return $user;
    }

    /**
     * Get the Maniaplanet Client.
     *
     * @return ManiaplanetProvider|OAuth2Client
     */
    private function getManiaplanetClient()
    {
        return $this->clientRegistry->getClient('maniaplanet');
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->flashBag->add('error', 'Login has expired or failed!');
        return new RedirectResponse($this->router->generate('homepage'));
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
    }
}
