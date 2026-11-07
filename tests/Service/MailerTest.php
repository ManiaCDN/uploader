<?php

namespace App\Tests\Service;

use App\Entity\ManiaplanetUser;
use App\Repository\ManiaplanetUserRepository;
use App\Service\Mailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class MailerTest extends TestCase
{
    private $swiftMailer;
    private $sentMessages;
    private $twig;
    private $requestStack;
    private $userRepository;
    private $containerBag;
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->sentMessages = [];

        $this->swiftMailer = $this->getMockBuilder(\Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->swiftMailer->method('send')
            ->willReturnCallback(function (\Swift_Message $message) {
                $this->sentMessages[] = $message;
                return 1;
            });

        $this->twig = $this->createMock(\Twig\Environment::class);
        $this->twig->method('render')->willReturn('<html>rendered email body</html>');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);

        $this->userRepository = $this->getMockBuilder(ManiaplanetUserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerBag = $this->createMock(ContainerBagInterface::class);
        $this->containerBag->method('get')->willReturnMap([
            ['app.email_sender_address', 'noreply@example.com'],
            ['app.email_reviewer_address', 'reviewer@example.com'],
        ]);

        $this->mailer = new Mailer(
            $this->swiftMailer,
            $this->userRepository,
            $this->twig,
            $this->requestStack,
            $this->containerBag
        );
    }

    public function testSendReviewNotificationSendsEmailToFileOwner()
    {
        $user = $this->createUser('testuser', 'test@example.com', true);
        $this->userRepository->method('findOneBy')
            ->with(['login' => 'testuser'])
            ->willReturn($user);

        $this->mailer->sendReviewNotification([
            'testuser' => ['testuser/file.txt' => 'approved'],
        ]);

        $this->assertCount(1, $this->sentMessages);
        $message = $this->sentMessages[0];
        $this->assertSame('ManiaCDN admin reviewed your files', $message->getSubject());
        $this->assertSame(['test@example.com'], array_keys($message->getTo()));
        $this->assertSame('<html>rendered email body</html>', $message->getBody());
    }

    public function testSendReviewNotificationSkipsUnknownUser()
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->mailer->sendReviewNotification([
            'ghost' => ['ghost/file.txt' => 'approved'],
        ]);

        $this->assertCount(0, $this->sentMessages);
        $flashes = $this->requestStack->getSession()->getFlashBag()->peekAll();
        $this->assertNotEmpty($flashes['danger']);
    }

    public function testSendReviewNotificationSkipsInvalidEmail()
    {
        $user = $this->createUser('testuser', 'not-an-email', true);
        $this->userRepository->method('findOneBy')->willReturn($user);

        $this->mailer->sendReviewNotification([
            'testuser' => ['testuser/file.txt' => 'approved'],
        ]);

        $this->assertCount(0, $this->sentMessages);
        $flashes = $this->requestStack->getSession()->getFlashBag()->peekAll();
        $this->assertNotEmpty($flashes['warning']);
    }

    public function testSendReviewNotificationSkipsUserWithoutPermission()
    {
        $user = $this->createUser('testuser', 'test@example.com', false);
        $this->userRepository->method('findOneBy')->willReturn($user);

        $this->mailer->sendReviewNotification([
            'testuser' => ['testuser/file.txt' => 'approved'],
        ]);

        $this->assertCount(0, $this->sentMessages);
        $flashes = $this->requestStack->getSession()->getFlashBag()->peekAll();
        $this->assertNotEmpty($flashes['warning']);
    }

    public function testSendBlockedFilesReminder()
    {
        $this->mailer->sendBlockedFilesReminder();

        $this->assertCount(1, $this->sentMessages);
        $message = $this->sentMessages[0];
        $this->assertSame('ManiaCDN: files awaiting validation', $message->getSubject());
        $this->assertSame(['reviewer@example.com'], array_keys($message->getTo()));
    }

    private function createUser(string $login, string $email, bool $notificationsEnabled): ManiaplanetUser
    {
        $user = new ManiaplanetUser();
        $user->setLogin($login);
        $user->setNickname($login);
        $user->setEmail($email);
        $user->setRole('ROLE_USER');
        $user->setEmailSendApprovalNotification($notificationsEnabled);

        return $user;
    }
}
