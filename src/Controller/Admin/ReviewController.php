<?php
/**
 * Helps the admin to review files which are not yet approved
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Controller\Admin;

use App\Entity\Path;
use App\Service\BlockedFilesManager;
use App\Service\FilesystemManager;
use App\Service\Mailer;
use Ckr\Util\ArrayMerger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReviewController extends AbstractController
{
    private $bfm;
    private $fsm;
    private $request;
    private $authChecker;
    private $mailer;
    
    public function __construct(
            BlockedFilesManager $bfm,
            FilesystemManager $fsm,
            AuthorizationCheckerInterface $authChecker,
            Mailer $mailer
    ) {
        $this->bfm = $bfm;
        $this->fsm = $fsm;
        $this->authChecker = $authChecker;
        $this->mailer = $mailer;
        $this->request = Request::createFromGlobals();
    }
    
    public function show()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        // (un-)blocks files, deletes also
        $this->blockDeleteAction();
        
        $list_raw = $this->bfm->read(false);
        $list = [];
        foreach ($list_raw as $path_raw) {
            $tmp = new Path();
            $tmp->fromString($path_raw);
            $list[] = $tmp;
        }
        return $this->render('admin/review/index.html.twig', [
            'list' => $list,
        ]);
    }
    
    public function download()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        $file = $this->request->query->get('file');
        $pathname = $_ENV['UPLOAD_DIR'].'/'.$file;
        
        $spl = new \SplFileInfo($pathname); // why doesn't spl provide MIME types?
        $finfo = new \finfo(FILEINFO_MIME);
        
        $response = new BinaryFileResponse($pathname);
        $response->headers->set('Content-Type', $finfo->file($pathname));
        $response->headers->set('Content-Length', (string) $spl->getSize());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($file)
        );
        
        return $response->send();
    }
    
    /**
     * Block files according to form (in-table) submitted by ADMIN
     * 
     * @throws \Exception
     */
    private function blockDeleteAction()
    {
        $blocks_raw = $this->request->request->all('block') ?? [];
        $delete_raw = $this->request->request->all('delete') ?? [];
        $token  = $this->request->request->get('token');
        
        if (empty($blocks_raw)) {
            // blocks will always list at least one file, if the folder wasn't empty
            // and user still pressed update button. nothing to do here
            return;
        }
        
        if (!$this->isCsrfTokenValid('admin_block-delete', $token)) {
            throw new \Exception('CSRF token invalid!');
        }
        
        // block:
        $blocks = [];
        foreach ($blocks_raw as $name => $status) {
            $tmp = new Path();
            $tmp->fromString($name);
            $tmp->setBlocked(filter_var($status, FILTER_VALIDATE_BOOLEAN));
            $blocks[] = $tmp;
        }
        $block_changelog = $this->bfm->block($blocks);
        
        // delete
        $delete = [];
        foreach ($delete_raw as $name => $status) {
            $tmp = new Path();
            $tmp->fromString($name);
            $tmp->setDelete(filter_var($status, FILTER_VALIDATE_BOOLEAN));
            $delete[] = $tmp;
        }
        $delete_changelog = $this->fsm->delete($delete);
        
        // using this library because php's array_merge_recursive merges
        // duplicates into another array as described here:
        // https://www.php.net/manual/en/function.array-merge-recursive.php#92195
        $changelog = ArrayMerger::doMerge($block_changelog, $delete_changelog);
        $this->mailer->sendReviewNotification($changelog);
    }
}
