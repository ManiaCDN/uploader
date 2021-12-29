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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
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
    private $flashBag;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        FlashBagInterface $flashBag
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->flashBag = $flashBag;
    }

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
                $user->setEmailSendApprovalNotification(false);

                $this->entityManager->persist($user); // tell doctrine about the change
                $this->entityManager->flush(); // let doctrine execute it and run the query to the database

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->flashBag->add('error', 'Login has expired or failed!');
        return new RedirectResponse($this->router->generate('homepage'));
    }
}
