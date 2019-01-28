<?php
/**
 * Any security related methods go here.
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Service;

use Symfony\Component\Security\Core\User\UserInterface;

class Security
{
    /**
     * Check if the given user is allowed to write files or directories.
     * $path must start with the directory of the user.
     * eg $path = 'login/path/to/somewhere';
     * 
     * @param string $path
     * @param UserInterface $user
     * @return boolean
     */
    public function isAllowedToWrite(string $path, UserInterface $user): bool {
        if (true === in_array('ROLE_BANNED', $user->getRoles())) {
            return false;
        }
        
        if (true === in_array('ROLE_ADMIN', $user->getRoles()) ||
            $user->getUsername() == $this->pathLogin($path)
        ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Gets the owner of a directory
     * $path must start with the suspected owner
     * e.g. login/path/to/somewhere
     * 
     * @param string $path
     * @return string
     */
    public function pathLogin(string $path): string {
        $path = trim($path, '/');
        $array = explode('/', $path);
        return $array[0];
    }
    
    /**
     * Checks if a $path has .. in order to go one level up.
     * If no .. is found, it returns the path unchanged
     * If at least one is found, it returns false and optionally
     * throws an exeption (if $throw == true).
     * 
     * @param string $path
     * @param type $throw
     * @throws \Exception
     */
    public function checkDirUp(string $path, $throw = true) {
        // counts the occurences of needle by reference into $found
        $found = 0;
        $cleanPath = str_replace('..', '', $path, $found);
        
        if (0 == $found) {
            // good guy
            return $cleanPath;
        } else {
            // bad guy tried to hack
            if ($throw) {
                throw new \Exception('Usage of .. in paths is not allowed.');
            } else {
                return false;
            }
        }
    }
}