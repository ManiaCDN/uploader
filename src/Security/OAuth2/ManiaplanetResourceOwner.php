<?php
/**
 * ManiaplanetResourceOwner.php
 * 
 * @author     Tom Valk <tomvalk@lt-box.info>
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2017 Tom Valk, 2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */

namespace App\Security\OAuth2;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;


class ManiaplanetResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response payload.
     *
     * @var
     */
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->response['login'];
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }

    /**
     * Get Maniaplanet login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->response['login'];
    }

    /**
     * Get Email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->response['email'];
    }

    /**
     * Get Nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->response['nickname'];
    }

    /**
     * Get Path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->response['path'];
    }
}
