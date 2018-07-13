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

use App\Service\BlockedFilesManager;
use App\Service\FilesystemManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReviewController extends Controller
{
    private $bfm;
    private $fsm;
    private $request;
    private $authChecker;
    
    public function __construct(
            BlockedFilesManager $bfm,
            FilesystemManager $fsm,
            AuthorizationCheckerInterface $authChecker
    ) {
        $this->bfm = $bfm;
        $this->fsm = $fsm;
        $this->authChecker = $authChecker;
        $this->request = Request::createFromGlobals();
    }
    
    public function show()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        // (un-)blocks files, deletes also
        $this->blockDeleteAction();
        
        $list = $this->bfm->read(false);
        return $this->render('admin/review/index.html.twig', [
            'list' => $list,
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
        
        if (!$this->isCsrfTokenValid('admin_block-delete', $token)) {
            throw new \Exception('CSRF token invalid!');
        }
        
        // block
        if (true === $this->authChecker->isGranted('ROLE_ADMIN')) {
            $this->bfm->block($blocks, true); // second param: inform user by email
        }
        
        // delete
        $this->fsm->delete($delete);
    }
}
