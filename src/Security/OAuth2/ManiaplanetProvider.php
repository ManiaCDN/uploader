<?php
/**
 * ManiaplanetProvider.php
 * 
 * @author     Tom Valk <tomvalk@lt-box.info>
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2017 Tom Valk, 2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */

namespace App\Security\OAuth2;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;


class ManiaplanetProvider extends AbstractProvider
{
    const BASE_URL = 'https://v4.live.maniaplanet.com/';

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return self::BASE_URL . 'login/oauth2/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return self::BASE_URL . 'login/oauth2/access_token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return ''; // ignore
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $urls = [
            self::BASE_URL . 'webservices/me',
            self::BASE_URL . 'webservices/me/email'
        ];

        $details = [];

        // get main details.
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, self::BASE_URL . 'webservices/me', $token);
        $details = $this->getParsedResponse($request);

        // get email.
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, self::BASE_URL . 'webservices/me/email', $token);
        $details['email'] = $this->getParsedResponse($request);

        return $details;
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['basic', 'email']; // more: 'titles', 'events', 'maps'
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHeaders()
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ManiaplanetResourceOwner($response);
    }

    public function getAccessToken($grant, array $options = [])
    {
        return parent::getAccessToken($grant, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationHeaders($token = null)
    {
        if ($token instanceof AccessToken) {
            return [
                'Authorization' => 'Bearer '.$token->getToken()
            ];
        }
    }
}
