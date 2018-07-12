<?php
/**
 * Major part of the site.
 * Responsible for the file browser which also hosts
 * upload, file deletions, unblocking etc.
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Controller;

use App\Service\BlockedFilesManager;
use App\Service\FilesystemManager;
use App\Service\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BrowseController extends Controller
{
    private $bfm;
    private $authChecker;
    private $fsm;
    private $security;
    private $request;
    private $path;

    public function show(BlockedFilesManager $bfm,
            AuthorizationCheckerInterface $authChecker,
            FilesystemManager $fsm,
            Security $security
    ) {
        $this->bfm = $bfm;
        $this->authChecker = $authChecker;
        $this->fsm = $fsm;
        $this->security = $security;
        $this->request = Request::createFromGlobals();
        $this->path = $this->security->checkDirUp($this->request->query->get('path', ''));
        
        // (un-)blocks files
        $this->blockAction();

        // deletes files
        $this->deleteAction();

        // create folder
        $this->createFolderAction();

        // create user folder
        $this->createUserFolderAction();
        
        // no folder yet? ask the user to create
        $this->fsm->userHasFolder();
        
        $list = $this->makeList($this->path);
        
        return $this->render('browse/index.html.twig', [
            'path'              => trim($this->path, '/'),
            'onedirup'          => trim(dirname($this->path, 1), '.'),
            'PUBLIC_UPLOAD_URL' => getenv('PUBLIC_UPLOAD_URL'),
            'list'              => $list,
            'blocklist'         => $this->bfm->read(false),
            'isAllowedToWrite'  => $this->security->isAllowedToWrite($this->path, $this->getUser()),
        ]);
    }
    
    /**
     * Block files according to form (in-table) submitted by ADMIN
     * 
     * @return bool
     */
    private function blockAction() {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            return null;
        }
        
        $blocks = $this->request->request->get('block', array());
        return $this->bfm->block($blocks, true); // second param: inform user by email
    }
    
    /**
     * Delete files. Just passing through ...
     * 
     * @return bool|null
     */
    private function deleteAction() {
        $delete = $this->request->request->get('delete', array());
        return $this->fsm->delete($delete);
    }
    
    /**
     * Creates any folder. Just passing through ...
     * 
     * @return bool
     */
    private function createFolderAction() {
        $folder = $this->request->request->get('newdir', false);
        
        if ($folder) {
            return $this->fsm->createFolder($this->path.'/'.$folder);
        }
    }
    
    /**
     * Creates the specific folder for the logged in user.
     * No user input here.
     * Just passing through ...
     * 
     * @return bool
     */
    private function createUserFolderAction() {
        if ($this->request->request->get('createuserfolder', false)) {
            return $this->fsm->createUserFolder();
        }
    }
    
    /**
     * Use Symfony's Finder component to get a directory listing
     * 
     * @param string $path
     * @return Finder
     */
    private function makeList(string $path): Finder {
        $finder = new Finder();
        
        $list = $finder
                ->depth('== 0') // don't recurse deeper
                ->sortByType() // show directories first, then files
                ->in(getenv('UPLOAD_DIR').'/'.$path); // finally give the path and run
        
        return $list;
    }
}
