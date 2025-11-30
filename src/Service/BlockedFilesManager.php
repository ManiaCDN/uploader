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

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Path;

class BlockedFilesManager
{
    private ContainerBagInterface $containerBag;

    public function __construct(ContainerBagInterface $containerBag) {
        $this->containerBag = $containerBag;
    }

    /**
     * Takes an array of App\Service\Path's to be blocked or unblocked.
     * There are attributes of Path indicating whether
     * it should be blocked.
     * 
     * Returns an array containing the paths as strings
     * where changes were made.
     * Structure:
     * "login" => [
     *   "path/to/file" => *string of what happened to the file*
     * ]
     * 
     * @param array $files
     * @return array Changes made
     */
    public function block(array $files): array
    {
        if (empty($files)) {
            return [];
        }
        
        $filesBlocked = $this->read();
        $changes = array(); // record which files are affected
        
        foreach ($files as $path) {
            $pathname = $path->getString();
            $owner = $path->getOwnerLogin();
            
            // only record those where there actually was a change
            if (true == $path->getBlocked() && !isset($filesBlocked[$pathname])) {
                // should be blocked
                // add this path. uniqid() needed, because we need a
                // key for flipping back (which has to be unique,
                // in order not to overwrite other keys)
                $filesBlocked[$pathname] = uniqid();
                $changes[$owner][$pathname] = 'rejected';
            } elseif (false == $path->getBlocked() && isset($filesBlocked[$pathname])) {
                unset($filesBlocked[$pathname]);
                $changes[$owner][$pathname] = 'approved';
            }
        }
        
        $this->write($filesBlocked);
        
        return $changes;
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
        $file = file($this->getPathToBlockedFilesFile(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
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
        file_put_contents($this->getPathToBlockedFilesFile(), $filesBlockedString);
    }

    private function getPathToBlockedFilesFile(): string {
        $blockedFilesPath = $_ENV['BLOCKED_FILES_LIST'];
        
        if ($this->isAbsolutePath($blockedFilesPath)) {
            return $blockedFilesPath;
        }
        
        return Path::makeAbsolute(
            $blockedFilesPath, 
            $this->containerBag->get('kernel.project_dir') . '/public'
        );
    }
    
    private function isAbsolutePath(string $path): bool {
        if (Path::isAbsolute($path)) {
            return true;
        }
        
        // Additional check for stream wrappers (vfs://, file://, etc.)
        // because their path cannot simply be prefixed with an absolute path
        // (mostly relevant for tests with vfs)
        return str_contains($path, '://');
    }
}
