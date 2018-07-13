<?php
/**
 * Files on ManiaCDN must be human-validated before they
 * are distributed through rsync to its mirrors.
 * rsync reads the file managed by this class to prevent
 * them from being pulled by the mirrors.
 * It therefore follows a blacklist system.
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Service;

use App\Service\Mailer;
use App\Service\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BlockedFilesManager
{
    private $mailer;
    private $session;
    
    private $blocked_files_list;
    
    public function __construct(
            Mailer $mailer,
            Security $security,
            SessionInterface $session
    ) {
        $this->mailer = $mailer;
        $this->security = $security;
        $this->session = $session;
        
        $this->blocked_files_list = getenv('BLOCKED_FILES_LIST');
    }
    
    /**
     * Takes an array of files to be blocked or unblocked
     * Structure:
     * filename => (bool)
     * While the boolean value indicates whether the file is blocked
     * true: blocked; false: not blocked
     * 
     * Second argument is whether the owner of the affected files
     * should be notified by email about the change.
     * This is set to false e.g. when a new file is uploaded,
     * as the upload also triggers an initial block.
     * 
     * Returns
     * - null if the given array was empty
     * - false if one value in $files is not boolean
     * - true on success
     * 
     * @param array $files
     * @param bool $informOwner
     * @return bool|null
     */
    public function block(array $files, bool $informOwner): ?bool
    {
        if (empty($files)) {
            return null;
        }
        
        $filesBlocked = $this->read();
        $changes = array(); // record which files are affected
        
        foreach ($files as $pathname => $block) {
            $pathname = trim($pathname, '/');
            // smart recognition of strings like 'false', 'yes' ...
            $block = filter_var($block, FILTER_VALIDATE_BOOLEAN);
            $owner = $this->security->pathLogin($pathname);
            
            if (true == $block && !isset($filesBlocked[$pathname])) {
                // should be blocked
                // add this path. uniqid() needed, because we need a
                // key for flipping back (which has to be unique,
                // in order not to overwrite other keys)
                $filesBlocked[$pathname] = uniqid();
                $changes[$owner][$pathname] = true;
            } elseif (false == $block && isset($filesBlocked[$pathname])) {
                unset($filesBlocked[$pathname]);
                $changes[$owner][$pathname] = false;
            }
        }
        
        $this->write($filesBlocked);
        
        if ($informOwner) {
            $this->mailer->blockMessage($changes);
        }
        
        return true;
    }

    /**
     * Reads the "\n" separated file of blocked files into an array.
     * Optionally inverts (flip) the array key and value pairs
     * 
     * @param bool $flip
     * @return array
     */
    public function read(bool $flip = true): array {
        // parse file into array similar to [n] => file.name
        $file = file($this->blocked_files_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($flip) {
            // invert keys and values to [file.name] => n
            $file = array_flip($file);
        }
        return $file;
    }
    
    /**
     * Counterpart to read()
     * Takes an array of the new list of blocked files
     * and writes it.
     * 
     * @param array $filesBlocked
     */
    private function write(array $filesBlocked) {
        // backflip 8) now everything is in normal order. also glue with new lines together
        $filesBlockedString = implode("\n", array_flip($filesBlocked)); 
        
        // save
        file_put_contents($this->blocked_files_list, $filesBlockedString);
    }
}