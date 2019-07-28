<?php
/**
 * This class represents all paths to user content files, always relative
 * to the root of the user folders. So the first atom is the login.
 * 
 * It also automatically makes sure that it never points to an item outside the
 * base path UPLOAD_DIR (from env).
 */

namespace App\Service;

use Webmozart\PathUtil\Path as PU;
use Symfony\Component\Security\Core\User\UserInterface;

class Path {
    /**
     * A path is stored as a canonicalized (normalized) array with each atom
     * of the path being one key of the array.
     * Root path is represented by a one-key array with the value being an empty
     * string.
     */
    private $path;
    
    // metadata for usage by other classes, not used in this class
    /**
     * @var bool true if the file is / should be blocked
     */
    private $blocked;
    
    /**
     * @var bool true if the file is / should be deleted
     */
    private $delete;
    
    public function __construct() {
        
    }
    
    public function setBlocked(bool $var) {
        $this->blocked = $var;
    }
    
    public function getBlocked(): bool {
        return $this->blocked;
    }
    
    public function setDelete(bool $var) {
        $this->delete = $var;
    }
    
    public function getDelete(): bool {
        return $this->delete;
    }
    
    /**
     * Set the path from a given string.
     * Attention: starting '/' causes an empty first atom and
     * owner checks will fail and much more.
     * This should only be used once on one object!
     * 
     * @param string $s
     * @throws \Exception
     */
    public function fromString(string $s): void {
        $s = trim($s, '/');
        $s = self::cleanPath($s, true);
        $this->path = explode('/', PU::canonicalize($s));
        if (!$this->isLegal()) {
            throw new \Exception('A path was provided to App\Entity\Path that is not inside the base path / upload directory. Path is: '.$s);
        }
    }
    
    
    /**
     * Get the string representation of this path.
     * 
     * @return string
     */
    public function getString(): string {
        $s = implode('/', $this->path);
        return $s;
    }
    
    /**
     * Get the i'th atom of the path.
     * 
     * @param int $i
     * @return string|null
     */
    public function getAtom(int $i): ?string {
        if (isset($this->path[$i])) {
            return $this->path[$i];
        }
        else {
            return null;
        }
    }
    
    public function getArray(): array {
        return $this->path;
    }

    /**
     * Gives a new path object that points to the directory
     * $n levels above the current one.
     * If $n exceeds the depth of the path it returns the root.
     * examples
     * $n = 1 means the immediate parent,
     * $n = 0 is the current folder
     * 
     * @param int $n
     * @return Path
     * @throws \Exception
     */
    public function getParentPath(int $n = 1): self {
        if ($n < 0) {
            throw new \Exception('getParentPath() received an invalid argument: '.$n.'. $n might not be negative.');
        }
        
        $parent = clone $this;
        if ($n >= $this->getDepth()) {
            $parent->path = ['']; // root
        }
        else {
            $parent->path = array_slice($this->path, 0, - $n); // cut array from behind
        }
        
        return $parent;
    }
    
    /**
     * Returns the URL the file can be reached with according to 
     * the correspondent env var.
     * 
     * @return string
     */
    public function getPublicURL(): string {
        return PU::join(getenv('PUBLIC_UPLOAD_URL'), $this->getString());
    }
    
    /**
     * Gives the full path in the file system.
     * Depends on the UPLOAD_DIR env var.
     * 
     * @return string
     */
    public function getAbsolutePath(): string {
        return PU::join(getenv('UPLOAD_DIR'), $this->getString());
    }
    
    /**
     * Return the depth of the path
     * Examples:
     * 0: should not occur
     * 1: foo
     * 2: /foo, foo/, foo/bar
     * 
     * @return int
     */
    public function getDepth(): int {
        return count($this->path);
    }
    
    /**
     * Returns a cloned object with a single atom appended to the path.
     * No slashes allowed!
     * 
     * @param string $with
     * @return Path a clone of $this
     * @throws \Exception
     */
    public function append(string $with): self {
        if (strpos($with, '/')) {
            throw new \Exception('Paths may only be appended with atoms. No slashes allowed.');
        }
        
        $with = self::cleanPath($with, false);
        
        $clone = clone $this;
        $clone->path[] = $with;
        return $clone;
    }
    
    /**
     * Check if the given user is allowed to write
     * 
     * @param UserInterface $user
     * @return boolean
     */
    public function isWritableBy(UserInterface $user): bool {
        // banned users simply never write anywhere
        if (true === in_array('ROLE_BANNED', $user->getRoles())) {
            return false;
        }
        
        return (
            true === in_array('ROLE_ADMIN', $user->getRoles()) ||
            $user->getUsername() == $this->getOwnerLogin()
        );
    }
    
    /**
     * Gets the owner (login) this path (operates on the object only).
     * This might give you a string that is not a valid login.
     * Returns an empty string if it's the root folder
     * 
     * @return string
     */
    public function getOwnerLogin(): string {
        return $this->getAtom(0);
    }
    
    /**
     * Checks if the path is pointing to a folder inside the base path.
     * 
     * @return bool
     */
    public function isLegal(): bool {
        // empty path / root only is legal
        if ($this->isRoot()) {
            return true;
        }
        
        // make sure that our path doesn't go "upwards"
        // Workaround: isBasePath doesn't work if the first argument is ./
        // so we have to prefix some nonsense to both folders
        return PU::isBasePath('./ihatebugs', './ihatebugs/'.$this->getString());
    }
    
    /**
     * True if path is root folder
     * 
     * @return bool
     */
    public function isRoot(): bool {
        return $this->path == [''];
    }
    
    /**
     * Removes special chars and replaces them with underscore.
     * 
     * @param string $path
     * @param bool $acceptslashes
     * @return string
     */
    public static function cleanPath(string $path, bool $acceptslashes): string {
        if ($acceptslashes) {
            return preg_replace('/[^A-Za-z0-9-._\/]/', '_', $path);
        }
        else {
            return preg_replace('/[^A-Za-z0-9-._]/', '_', $path);
        }
    }
}
