<?php
/**
 * ManiaplanetUser.php
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/askuri/maniaplanet-oauth-symfony
 */

namespace App\Entity;

use App\Security\OAuth2\ManiaplanetResourceOwner;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ManiaplanetUserRepository")
 * @ORM\Table(name="maniaplanet_user", 
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="login",
 *            columns={"login"})
 *    }
 * )
 */
class ManiaplanetUser implements UserInterface, \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $nickname;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $role;

    /**
     * @ORM\Column(type="boolean")
     */
    private $email_send_approval_notification;

    public function getUserIdentifier() {
        return $this->login;
    }

    /**
     * @deprecated remove with Symfony 6, getUserIdentifier is the replacement
     */
    public function getUsername()
    {
        return $this->login;
    }
    
    public function getRoles()
    {
        //return $this->roles;
        return array($this->role);
    }

    public function getSalt()
    {
        // we don't need a salt as we are using a token
        return null;
    }

    public function getPassword()
    {
        // we are using tokens, no passwords
        return "";
    }
    
    public function eraseCredentials()
    {
    }
    
    /**
     * Update the properties.
     * Used when user logs in to update the database (e.g. email has changed)
     * Returns true if any of the vars below has changed its value (triggers db update)
     * 
     * @param ManiaplanetResourceOwner $maniaplanetUser
     * @return boolean
     */
    public function updateFromManiaplanetUser(ManiaplanetResourceOwner $maniaplanetUser) {
        $change = false;
        
        if ($this->email != $maniaplanetUser->getEmail()) {
            $this->email = $maniaplanetUser->getEmail();
            $change = true;
        }
        
        if ($this->nickname != $maniaplanetUser->getNickname()) {
            $this->nickname = $maniaplanetUser->getNickname();
            $change = true;
        }
        
        return $change;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
    
    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->login,
            $this->nickname,
            $this->email,
        ));
    }
    
    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->login,
            $this->nickname,
            $this->email,
        ) = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function getEmailSendApprovalNotification(): ?bool
    {
        return $this->email_send_approval_notification;
    }

    public function setEmailSendApprovalNotification(bool $email_send_approval_notification): self
    {
        $this->email_send_approval_notification = $email_send_approval_notification;

        return $this;
    }
}
