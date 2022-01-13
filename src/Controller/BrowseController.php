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

use App\Entity\Path;
use App\Service\BlockedFilesManager;
use App\Service\FilesystemManager;
use App\Service\Mailer;
use Ckr\Util\ArrayMerger;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BrowseController extends AbstractController
{
    private $storage;
    private $bfm;
    private $authChecker;
    private $fsm;
    private $mailer;
    private $request;

    /**
     * @param FilesystemOperator $masterStorage named-auto-wiring to match 'master.storage' from flysystem.yaml
     */
    public function __construct(
        FilesystemOperator $masterStorage,
        BlockedFilesManager $bfm,
        AuthorizationCheckerInterface $authChecker,
        FilesystemManager $fsm,
        Mailer $mailer
    ) {
        $this->storage = $masterStorage;
        $this->bfm = $bfm;
        $this->authChecker = $authChecker;
        $this->fsm = $fsm;
        $this->mailer = $mailer;
    }

    public function show(Request $request) {
        $this->request = $request;
        
        // parse the path into a Path object. Check for being in the base path included
        // trim / as they don't have any importance but would cause some special
        // handling if they were taken to the path
        $path = new Path();
        $path->fromString($request->query->get('path', '.'));
        
        // (un-)blocks files, deletes files
        $this->blockDeleteAction();

        // create folder
        $this->createFolderAction($path);

        // create user folder in case it isn't created yet
        $this->fsm->createUserFolder();
        
        try {
            $list = $this->makeDirectoryListing($path);
        }
        catch (DirectoryNotFoundException $e) { // todo use appropriate exception
            // not found: show empty folder with an alert
            $request->getSession()->getFlashBag()->add('danger', 'The folder "'.$path->getString().'" does not exist. It was probably deleted. You are now seeing the overview.');
            $path->fromString(''); // reset path to root folder
            $list = $this->makeDirectoryListing($path);
        }
        
        return $this->render('browse/index.html.twig', [
            'path'              => $path,
            'list'              => $list,
        ]);
    }
    
    /**
     * Block files according to form (in-table) submitted by ADMIN
     * 
     * @throws \Exception
     */
    private function blockDeleteAction() {
        $blocks_raw = $this->request->request->all('block') ?? [];
        $delete_raw = $this->request->request->all('delete') ?? [];
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
                $tmp = new Path();
                $tmp->fromString($name);
                $tmp->setBlocked(filter_var($status, FILTER_VALIDATE_BOOLEAN));
                $blocks[] = $tmp;
            }
            $block_changelog = $this->bfm->block($blocks);
        } else {
            $block_changelog = [];
        }
        
        // delete
        $delete = [];
        foreach ($delete_raw as $name => $status) {
            $tmp = new Path();
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
    private function createFolderAction($path) {
        $folder = $this->request->request->get('newdir', false);
        $token  = $this->request->request->get('token');
        
        if ($folder) {
            if (!$this->isCsrfTokenValid('browse_newfolder', $token)) {
                throw new \Exception('CSRF token invalid!');
            }
            
            $this->fsm->createFolder($path->append($folder));
        }
    }

    /**
     * @param Path $path
     * @return StorageAttributes[]
     * @throws FilesystemException
     */
    private function makeDirectoryListing(Path $path): array {
        $blockedFiles = $this->bfm->read(true);
        return $this->storage->listContents($path->getString())
            ->sortByPath()
            ->map(fn (StorageAttributes $attributes) => [
                    'path'       => new Path($attributes->path()),
                    'attributes' => $attributes,
                    'is_blocked' => array_key_exists($attributes->path(), $blockedFiles)
            ])
            ->toArray();
    }
}
