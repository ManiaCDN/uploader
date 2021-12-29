<?php
/**
 * Interacts with and manipulates the filesystem
 * Also provides some filesystem related methods
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Service;

use App\Entity\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FilesystemManager
{
    private $bfm;
    private $user;
    private $requestStack;
    
    private $filesystem;
    
    public function __construct(BlockedFilesManager $bfm,
            TokenStorageInterface $tokenStorage,
            RequestStack $requestStack
    ) {
        $this->bfm = $bfm;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->requestStack = $requestStack;
        
        $this->filesystem = new Filesystem();
    }
    
    /**
     * Deletes a whole bunch of files and:
     * - makes sure the logged in user has permission to do so.
     * - unblocks the file
     * 
     * $files is an array of Path objects.
     * Each object must have the delete attribute set
     * to determine, if it should be deleted
     * 
     * Returns an array containing the paths as strings
     * where changes were made.
     * Structure:
     * "login" => [
     *   "path/to/file" => *string of what happened to the file*
     * ]
     * 
     * @param array $files
     * @return array
     */
    public function delete(array $files): array
    {
        if (empty($files)) {
            return [];
        }
        
        $unblocklist = array();
        $changelog = array();
        
        foreach ($files as $path) {
            $pathname = $path->getString();
            $owner = $path->getOwnerLogin();
            $fullPath = $path->getAbsolutePath();
            
            // check if any of the pathnames aren't allowed to be written.
            // only checks for user roles, not in the file system
            if (!$path->isWritableBy($this->user)) {
                $this->requestStack->getSession()->getFlashBag()->add('danger', 'You\'re not allowed to delete this file: '.$pathname);
                continue;
            }
            
            // check if file or directory exists and if it can be deleted
            if (!is_writable($fullPath)) {
                $this->requestStack->getSession()->getFlashBag()->add('warning', '"'.$pathname.'" cannot be deleted because it either doesn\'t exist or writing permissions are missing.');
                continue;
            }
            
            if (is_dir($fullPath) && !$this->isDirEmpty($fullPath)) {
                $this->requestStack->getSession()->getFlashBag()->add('warning', 'Only empty folders can be deleted. Please delete all files inside "'.$pathname.'" first.');
                continue;
            }
            
            //remove
            $this->filesystem->remove($fullPath);
            
            // unblock (false means to unblock)
            // if it is deleted, it doesn't need to be blocked anymore
            $path->setBlocked(false); // whatever it was before, now it will be blocked
            $unblocklist[] = $path;
            $changelog[$owner][$pathname] = 'deleted';
        }
        
        // unblock now
        $this->bfm->block($unblocklist);
        
        $this->requestStack->getSession()->getFlashBag()->add('success', count($unblocklist).' file(s) were deleted.');
        
        return $changelog;
    }
    
    /**
     * Create a folder specified by $pathname
     * $pathname must look like user/dir/to/create
     * with the last element being the dir to be created 
     * 
     * @param Path $path
     * @return bool
     */
    public function createFolder(Path $path): bool {
        $dirToCreate = $path->getAbsolutePath();
        
        if ($path->isWritableBy($this->user)
            && !$this->filesystem->exists($dirToCreate)) {
            $this->filesystem->mkdir($dirToCreate);
            $this->requestStack->getSession()->getFlashBag()->add('success', 'Folder successfully created.');
            return true;
        } else {
            $this->requestStack->getSession()->getFlashBag()->add('danger', 'Could not create the folder. Do you have permission? Does the folder or file already exist?');
            return false;
        }
    }
    
    /**
     * Creates the user's own folder named by his/her own login
     */
    public function createUserFolder() {
        $this->filesystem->mkdir($_ENV['UPLOAD_DIR'].'/'.$this->user->getUsername());
    }
    
    /**
     * Checks whether a directory is empty.
     * "." and ".." natural occur in linux environments
     * ".profile" has occured to me outside ~ and i have no idea why.
     * but as it is not a relevant file for us, let's also exclude it.
     * 
     * @param string $dir
     * @return bool
     */
    public function isDirEmpty(string $dir): bool {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && $entry != ".profile") {
                return false;
            }
        }
        return true;
    }
}
