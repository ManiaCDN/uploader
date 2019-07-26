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

use App\Service\BlockedFilesManager;
use App\Service\Security;
use App\Service\Path;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;

class FilesystemManager
{
    private $bfm;
    private $security;
    private $user;
    private $session;
    private $request;
    //private $currentPath; disabled, used only by userHasFolder()
    private $twig;
    
    private $filesystem;
    
    public function __construct(BlockedFilesManager $bfm,
            Security $security,
            TokenStorageInterface $tokenStorage,
            SessionInterface $session,
            \Twig_Environment $twig
    ) {
        $this->bfm = $bfm;
        $this->security = $security;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->session = $session;
        $this->request = Request::createFromGlobals();
        //$this->currentPath = $this->security->checkDirUp($this->request->query->get('path', ''));
        $this->twig = $twig;
        
        $this->filesystem = new Filesystem();
    }
    
    /**
     * Deletes a whole bunch of files and:
     * - makes sure the logged in user has permission to do so.
     * - unblocks the file
     * 
     * $files must be constructed with the
     * KEY being the filename (!)
     * value being whatever you like
     * 
     * @param array $files
     * @return bool|null
     */
    public function delete(array $files): ?bool
    {
        if (empty($files)) {
            return null;
        }
        
        $unblocklist = array();
        
        foreach ($files as $pathname => $value) {
            $pathname = $this->security->checkDirUp($pathname); // strip ../
            $fullPath = getenv('UPLOAD_DIR').'/'.$pathname;
            
            // check if any of the pathnames aren't allowed to be written
            if (!$this->security->isAllowedToWrite($pathname, $this->user)) {
                $this->session->getFlashBag()->add('danger', 'You\'re not allowed to delete files here.');
                return false;
            }
            
            if (is_dir($fullPath)) {
                if (!$this->isDirEmpty($fullPath)) {
                    $this->session->getFlashBag()->add('warning', 'Only empty folders can be deleted.');
                    return false;
                }
            }
            
            //remove
            $this->filesystem->remove($fullPath);
            
            // unblock (false means to unblock)
            // if it is deleted, it doesn't need to be blocked anymore
            $unblocklist[$pathname] = false;
        }
        
        $this->bfm->block($unblocklist, false); // second param: user should not (false) be informed
        $this->session->getFlashBag()->add('success', count($unblocklist).' file(s) were deleted.');
        
        return true;
    }
    
    /**
     * Create a folder specified by $pathname
     * $pathname must look like user/dir/to/create
     * with the last element being the dir to be created 
     * 
     * @param string $path
     * @return bool
     */
    public function createFolder(Path $path): bool {
        $dirToCreate = $path->getAbsolutePath();
        
        if ($path->isWritableBy($this->user)
            && !$this->filesystem->exists($dirToCreate)) {
            $this->filesystem->mkdir($dirToCreate);
            $this->session->getFlashBag()->add('success', 'Directory successfully created.');
            return true;
        } else {
            $this->session->getFlashBag()->add('danger', 'Could not create the folder. Do you have permission? Does the folder or file already exist?');
            return false;
        }
    }
    
    /**
     * Check whether the user has his own folder yet.
     * CURRENTLY UNUSED. Remove?
     * 
     * @deprecated
     * @return bool
     */
    /*public function userHasFolder(): bool {
        if ($this->filesystem->exists(getenv('UPLOAD_DIR').'/'.$this->user->getUsername())) {
            if ($this->currentPath->getOwnerLogin() != $this->user->getUsername()) {
                $this->session->getFlashBag()->add('info', 'Please note that you can only upload files if you are in your own home directory. Click the "Go to my folder" button to always get there quickly.');
            }
            return true;
        } else {
            // this template contains a button which offers to create the folder
            $message = $this->twig->render('flashes/no_user_folder.html.twig');
            $this->session->getFlashBag()->add('warning', $message);
            return false;
        }
    }*/
    
    /**
     * Creates the user's own folder named by his/her own login
     */
    public function createUserFolder() {
        $this->filesystem->mkdir(getenv('UPLOAD_DIR').'/'.$this->user->getUsername());
        //$this->session->getFlashBag()->add('success', 'Folder successfully created. You will now find your own folder below. It\'s name is your Maniaplanet login.');
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