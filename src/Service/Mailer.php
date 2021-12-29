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
use Symfony\Component\HttpFoundation\RequestStack;

class Mailer
{
    private $mailer;
    private $userRepository;
    private $twig;
    private $requestStack;
    
    public function __construct(\Swift_Mailer $mailer,
            ManiaplanetUserRepository $userRepository,
            \Twig\Environment $twig,
            RequestStack $requestStack
    ) {
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->from = 'maniacdn-approval@askuri.de';
        $this->replyTo = 'info@maniacdn.net';
    }
    
    /**
     * Takes an array with this structure:
     * "login" => [
     *   "path/to/file.txt" => *string of what happened to the file*
     * ]
     * 
     * @param array $changes
     */
    public function sendReviewNotification(array $changes)
    {
        foreach ($changes as $owner => $files) {
            $user = $this->userRepository->findOneBy(['login' => $owner]);
            
            if (!$user) {
                $this->requestStack->getSession()->getFlashBag()->add('danger', 'Couldn\'t find a database entry for login "'.$owner.'"! Something is significantly wrong here! Changes were made though, email was just not sent.');
                continue;
            }
            
            // returns the email string if it is valid, (bool) false if invalid
            $email = filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                // it seems some users don't allow access their email address
                $this->requestStack->getSession()->getFlashBag()->add('warning', 'User '.$owner.' did not provide a valid email address. Thus the notification was not send.');
                continue;
            }
            
            // check whether this user gave permission to send emails
            if ($user->getEmailSendApprovalNotification() == 0) {
                $this->requestStack->getSession()->getFlashBag()->add('warning', 'User ' . $owner . ' did not give permission to send email notifications.');
                continue;
            }
            
            $body = $this->twig->render(
                'emails/review_notification.html.twig',
                [
                    'files' => $files,
                    'recipient' => $user->getUsername(),
                    'email' => $user->getEmail()
                ]
            );
            
            $message = (new \Swift_Message('ManiaCDN admin reviewed your files'))
                ->setFrom($this->from)
                ->setReplyTo($this->replyTo)
                ->setTo($user->getEmail())
                ->setBody($body, 'text/html')
            ;
            
            $this->mailer->send($message);
            
            $this->requestStack->getSession()->getFlashBag()->add('success', 'User notification was successfully sent to '.$owner.'.');
        }
    }
}
