<?php
/**
 * ManiaplanetAuthenticator.php
 *
 * @author     Tom Valk <tomvalk@lt-box.info>
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2017 Tom Valk, 2018,2021 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */


namespace App\Security\OAuth2;

use App\Entity\ManiaplanetUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

// your user entity

class ManiaplanetAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $entityManager;
    private $router;
    private $requestStack;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
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
    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() == '/connect/maniaplanet/finish';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('maniaplanet');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var ManiaplanetResourceOwner $maniaplanetResourceOwner */
                $maniaplanetResourceOwner = $client->fetchUserFromToken($accessToken);

                /** @var ManiaplanetUser $existingUser */
                $existingUser = $this->entityManager->getRepository(ManiaplanetUser::class)
                    ->findOneBy(['login' => $maniaplanetResourceOwner->getLogin()]);

                if ($existingUser) {
                    if ($existingUser->updateFromManiaplanetUser($maniaplanetResourceOwner)) {
                        // updateFromManiaplanetUser() updates the properties of its object using $maniaplanetUser
                        // if some properties have changed (email, nickname ...), updateFromManiaplanetUser() returns true
                        // -> changes are written into database
                        $this->entityManager->persist($existingUser);
                        $this->entityManager->flush();
                    }
                    return $existingUser;
                }

                // If not, register user by creating a new object for it and setting data from OAuth API
                $user = new ManiaplanetUser();
                $user->setEmail($maniaplanetResourceOwner->getEmail());
                $user->setNickname($maniaplanetResourceOwner->getNickname());
                $user->setLogin($maniaplanetResourceOwner->getLogin());
                $user->setRole('ROLE_USER');
                $user->setEmailSendApprovalNotification(true);

                $this->entityManager->persist($user); // tell doctrine about the change
                $this->entityManager->flush(); // let doctrine execute it and run the query to the database

                return $user;
            })
        );
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
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
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
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->requestStack->getSession()->getFlashBag()->add('error', 'Login has expired or failed!');
        return new RedirectResponse($this->router->generate('homepage'));
    }
}
