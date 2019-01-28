<?php
/**
 * Lists the registred users
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Controller\Admin;

use App\Repository\ManiaplanetUserRepository;
use App\Service\BlockedFilesManager;
use App\Service\FilesystemManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UsersController extends AbstractController
{
    private $userRepository;
    private $authChecker;
    private $request;
    
    public function __construct(
            ManiaplanetUserRepository $userRepository,
            AuthorizationCheckerInterface $authChecker
    ) {
        $this->userRepository = $userRepository;
        $this->authChecker = $authChecker;
        $this->request = Request::createFromGlobals();
    }
    
    public function show()
    {
        if (false === $this->authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only Admins allowed here.');
        }
        
        $list = $this->userRepository->findAll();
        
        return $this->render('admin/users/index.html.twig', [
            'list' => $list,
        ]);
    }
    
    /**
     * Block files according to form (in-table) submitted by ADMIN
     * 
     * @return bool
     */
    private function blockAction()
    {
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
    private function deleteAction()
    {
        $delete = $this->request->request->get('delete', array());
        return $this->fsm->delete($delete);
    }
}
