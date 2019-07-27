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
use App\Service\Mailer;
use App\Service\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Ckr\Util\ArrayMerger;

class BrowseController extends AbstractController implements ServiceSubscriberInterface
{
    private $bfm;
    private $authChecker;
    private $fsm;
    private $request;
    private $path;
    
    public function __construct()
    {
        
    }
    
    /**
     * Make the path service available through $this->get().
     * See: https://symfony.com/doc/current/service_container/service_subscribers_locators.html#defining-a-service-subscriber
     * It must inherit the services from the AbstractController.
     * 
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'path' => Path::class,
        ]);
    }

    public function show(BlockedFilesManager $bfm,
            AuthorizationCheckerInterface $authChecker,
            FilesystemManager $fsm,
            Mailer $mailer
            
    ) {
        $this->bfm = $bfm;
        $this->authChecker = $authChecker;
        $this->fsm = $fsm;
        $this->mailer = $mailer;
        $this->request = Request::createFromGlobals();
        
        // parse the path into a Path object. Check for being in the base path included
        // trim / as they don't have any importance but would cause some special
        // handling if they were taken to the path
        $this->path = $this->get('path');
        $this->path->fromString($this->request->query->get('path', '.'));
        
        
        // (un-)blocks files, deletes files
        $this->blockDeleteAction();

        // create folder
        $this->createFolderAction();

        // create user folder in case it isn't created yet
        $this->fsm->createUserFolder();
        
        $list = $this->makeList($this->path);
        
        return $this->render('browse/index.html.twig', [
            'path'              => $this->path,
            'list'              => $list,
            'blocklist'         => $this->bfm->read(false),
        ]);
    }
    
    /**
     * Block files according to form (in-table) submitted by ADMIN
     * 
     * @throws \Exception
     */
    private function blockDeleteAction() {
        $blocks_raw = $this->request->request->get('block', array());
        $delete_raw = $this->request->request->get('delete', array());
        $token  = $this->request->request->get('token');
        $submit = $this->request->request->get('submit_block-delete', false);
        
        // only continue if there is something to do
        if (false === $submit) {
            return;
        }
        
        if (!$this->isCsrfTokenValid('browse_block-delete', $token)) {
            throw new \Exception('CSRF token invalid!');
        }
        
        // block: only for admins
        if (true === $this->authChecker->isGranted('ROLE_ADMIN')) {
            // generate paths
            $blocks = [];
        
            foreach ($blocks_raw as $name => $status) {
                $tmp = $this->get('path'); // retrieve new instance
                $tmp->fromString($name);
                $tmp->setBlocked(filter_var($status, FILTER_VALIDATE_BOOLEAN));
                $blocks[] = $tmp;
            }
            $block_changelog = $this->bfm->block($blocks);
        }
        
        // delete
        $delete = [];
        foreach ($delete_raw as $name => $status) {
            $tmp = $this->get('path'); // retrieve new instance
            $tmp->fromString($name);
            $tmp->setDelete(filter_var($status, FILTER_VALIDATE_BOOLEAN));
            $delete[] = $tmp;
        }
        $delete_changelog = $this->fsm->delete($delete);
        
        // if an admin executed the action, send a notification
        if (true === $this->authChecker->isGranted('ROLE_ADMIN')) {
            // using this library because php's array_merge_recursive merges
            // duplicates into another array as described here:
            // https://www.php.net/manual/en/function.array-merge-recursive.php#92195
            $changelog = ArrayMerger::doMerge($block_changelog, $delete_changelog);
            $this->mailer->sendReviewNotification($changelog);
        }
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
            
            $this->fsm->createFolder($this->path->append($folder));
        }
    }
    
    /**
     * Use Symfony's Finder component to get a directory listing
     * 
     * @param string $path
     * @return Finder
     */
    private function makeList(Path $path): Finder {
        $finder = new Finder();
        
        $list = $finder
                ->depth('== 0') // don't recurse deeper
                ->sortByType() // show directories first, then files
                ->in($path->getAbsolutePath()); // finally give the path and run
        
        return $list;
    }
}
