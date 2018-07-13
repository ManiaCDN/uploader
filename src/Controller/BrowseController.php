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
        
        // (un-)blocks files, deltes files
        $this->blockDeleteAction();

        // create folder
        $this->createFolderAction();

        // create user folder
        $this->createUserFolderAction();
        
        // no folder yet? ask the user to create it
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
     * @throws \Exception
     */
    private function blockDeleteAction() {
        $blocks = $this->request->request->get('block', array());
        $delete = $this->request->request->get('delete', array());
        $token  = $this->request->request->get('token');
        
        if (empty($blocks)) {
            // blocks will always list at least one file, if the folder wasn't empty
            // and user still pressed update button. nothing to do here
            return;
        }
        
        if (!$this->isCsrfTokenValid('browse_block-delete', $token)) {
            throw new \Exception('CSRF token invalid!');
        }
        
        // block
        if (true === $this->authChecker->isGranted('ROLE_ADMIN')) {
            $this->bfm->block($blocks, true); // second param: inform user by email
        }
        
        // delete
        $this->fsm->delete($delete);
    }
    
    /**
     * Creates any folder. Just passing through ...
     * 
     * @throws \Exception
     */
    private function createFolderAction() {
        $folder = $this->request->request->get('newdir', false);
        $token  = $this->request->request->get('token');
        
        if ($folder) {
            if (!$this->isCsrfTokenValid('browse_newfolder', $token)) {
                throw new \Exception('CSRF token invalid!');
            }
            
            $this->fsm->createFolder($this->path.'/'.$folder);
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
