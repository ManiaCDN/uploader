<?php
/**
 * Responsible for generating email notifications.
 * 
 * @author     Martin Weber <enwi2@t-online.de>
 * @copyright  2018 Martin Weber
 * @license    https://www.gnu.org/licenses/gpl.txt  GNU GPL v3
 * @link       https://github.com/ManiaCDN/uploader
 */

namespace App\Service;

use App\Repository\ManiaplanetUserRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Mailer
{
    private $mailer;
    private $userRepository;
    private $twig;
    private $adminEmail;
    
    public function __construct(\Swift_Mailer $mailer,
            ManiaplanetUserRepository $userRepository,
            \Twig_Environment $twig,
            SessionInterface $session,
            $adminEmail
    ) {
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        $this->twig = $twig;
        $this->session = $session;
        $this->adminEmail = $adminEmail;
    }
    
    /**
     * Takes an array with this structure:
     * "login" => [
     *   "path/to/file.txt" => true
     * ]
     * Blocks files and informs the owner about it by email
     * 
     * @param array $changes
     */
    public function blockMessage(array $changes)
    {
        foreach ($changes as $owner => $files) {
            $user = $this->userRepository->findOneBy(['login' => $owner]);
            
            // returns the email string if it is valid, (bool) false if invalid
            $email = filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                // it seems some users don't allow access their email address
                $this->session->getFlashBag()->add('warning', 'User did not provide a valid email address. Thus the notification was not send.');
                continue;
            }
            
            $body = $this->twig->render(
                'emails/files_blocked.txt.twig',
                [
                    'files' => $files,
                    'recipient' => $user->getUsername(),
                ]
            );
            
            $message = (new \Swift_Message('ManiaCDN admin reviewed your files'))
                ->setFrom($this->adminEmail)
                ->setTo($user->getEmail())
                ->setBody($body, 'text/plain')
            ;
            
            $this->mailer->send($message);
            
            $this->session->getFlashBag()->add('success', 'User notification was successfully sent by email.');
        }
    }
}