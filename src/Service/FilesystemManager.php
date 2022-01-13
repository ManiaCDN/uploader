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
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FilesystemManager
{
    private $bfm;
    private $user;
    private $requestStack;
    
    private $filesystem; // todo remove
    private $storage;

    /**
     * @param FilesystemOperator $masterStorage named-auto-wiring to match 'master.storage' from flysystem.yaml
     */
    public function __construct(
        BlockedFilesManager $bfm,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        FilesystemOperator $masterStorage
    ) {
        $this->bfm = $bfm;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->requestStack = $requestStack;
        
        $this->filesystem = new Filesystem();
        $this->storage = $masterStorage;
    }
    
    /**
     * Deletes a bunch of files and:
     * - makes sure the logged-in user has permission to do so.
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
     * @param Path[] $files
     * @return array
     */
    public function delete(array $files): array
    {
        if (empty($files)) {
            return [];
        }
        
        $unblocklist = [];
        $changelog = [];
        
        foreach ($files as $path) {
            $pathWithFilename = $path->getString();
            $owner = $path->getOwnerLogin();

            if (!$path->isWritableBy($this->user)) {
                $this->requestStack->getSession()->getFlashBag()->add('danger', 'You\'re not allowed to delete this file: '.$pathWithFilename);
                continue;
            }

            if (!$this->storage->fileExists($pathWithFilename)) {
                $this->requestStack->getSession()->getFlashBag()->add('warning', '"'.$pathWithFilename.'" cannot be deleted because it either doesn\'t exist.');
                continue;
            }
            
            if ($this->storage-> ($fullPath) && !$this->isDirEmpty($fullPath)) {
                $this->requestStack->getSession()->getFlashBag()->add('warning', 'Only empty folders can be deleted. Please delete all files inside "'.$pathWithFilename.'" first.');
                continue;
            }
            
            //remove
            $this->filesystem->remove($fullPath);
            
            // unblock (false means to unblock)
            // if it is deleted, it doesn't need to be blocked anymore
            $path->setBlocked(false); // whatever it was before, now it will be blocked
            $unblocklist[] = $path;
            $changelog[$owner][$pathWithFilename] = 'deleted';
        }
        
        // unblock now
        $this->bfm->block($unblocklist);
        
        $this->requestStack->getSession()->getFlashBag()->add('success', count($unblocklist).' file(s) were deleted.');
        
        return $changelog;
    }

    public function createFolder(Path $path): bool {
        $dirToCreate = $path->getString();
        
        if ($path->isWritableBy($this->user) &&
            !$this->storage->fileExists($dirToCreate)
        ) {
            $this->storage->createDirectory($dirToCreate);
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
