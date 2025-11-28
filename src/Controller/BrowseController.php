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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use ZipStream;
use ZipStream\Option\Archive;

class BrowseController extends AbstractController
{
    private $bfm;
    private $authChecker;
    private $fsm;
    private $mailer;

    public function show(
        Request $request,
        BlockedFilesManager $bfm,
        AuthorizationCheckerInterface $authChecker,
        FilesystemManager $fsm,
        Mailer $mailer,
        Session $session
    ) {
        $this->bfm = $bfm;
        $this->authChecker = $authChecker;
        $this->fsm = $fsm;
        $this->mailer = $mailer;
        $this->session = $session;

        // parse the path into a Path object. Check for being in the base path included
        // trim / as they don't have any importance but would cause some special
        // handling if they were taken to the path
        $path = new Path();
        $path->fromString($request->query->get('path', '.'));

        // (un-)blocks files, deletes files
        $this->blockDeleteAction($request);

        // create folder
        $this->createFolderAction($request, $path);

        // create user folder in case it isn't created yet
        $this->fsm->createUserFolder();

        try {
            $list = $this->makeList($path);
        }
        catch (DirectoryNotFoundException $e) {
            // not found: show empty folder with an alert
            $session->getFlashBag()->add('danger', 'The folder "'.$path->getString().'" does not exist. It was probably deleted. You are now seeing the overview.');
            $path->fromString(''); // reset path to root folder
            $list = $this->makeList($path);
        }

        return $this->render('browse/index.html.twig', [
            'path'              => $path,
            'list'              => $list,
            'blocklist'         => $this->bfm->read(false),
        ]);
    }

    /**
     * Create a zip archive of .loc files used by Maniaplanet and Trackmania.
     * Requires GET parameter 'path' that gives the root of the zip that
     * should be created, relative to UPLOAD_DIR.
     *
     * @return StreamedResponse
     */
    public function downloadLocsAction(Request $request) {
        $path = new Path();
        $path->fromString($request->query->get('path', '.'));

        // get a list of files we need to create .loc files for
        $finder = new Finder();
        $list = $finder
                ->files() // look for files only, exclude directories
                ->in($path->getAbsolutePath());

        $response = new StreamedResponse(function() use ($list, $path) {
            $options = new Archive();

            $options->setContentType('application/octet-stream');
            $options->setZeroHeader(true); // this is needed to prevent issues with truncated zip files
            $options->setSendHttpHeaders(true); // let zipstream set the headers
            $options->setEnableZip64(false); // according to zipstream readme, zip64 can cause issues on MacOS and we don't need it

            $zip = new ZipStream\ZipStream('locators.zip', $options);

            foreach ($list as $file) {
                // to the path from the url (indicating the root of th archive)
                // we add the relative paths from there to each file that's 
                // going into the archive
                $filepath = $path->append($file->getRelativePathname(), true);

                // for each file we find, we create a new .loc file with the same name.
                // the content is the file's public URL
                $zip->addFile($filepath->getString().'.loc', $filepath->getPublicURL());
            }

            $zip->finish();
        });
        return $response;
    }

    /**
     * Block files according to form (in-table) submitted by ADMIN
     *
     * @throws \Exception
     */
    private function blockDeleteAction(Request $request) {
        $blocks_raw = $request->request->all('block') ?? [];
        $delete_raw = $request->request->all('delete') ?? [];
        $token  = $request->request->get('token');
        $submit = $request->request->get('submit_block-delete', false);

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
    private function createFolderAction(Request $request, $path) {
        $folder = $request->request->get('newdir', false);
        $token  = $request->request->get('token');

        if ($folder) {
            if (!$this->isCsrfTokenValid('browse_newfolder', $token)) {
                throw new \Exception('CSRF token invalid!');
            }

            $this->fsm->createFolder($path->append($folder));
        }
    }

    /**
     * Use Symfony's Finder component to get a directory listing
     *
     * @param Path $path
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
