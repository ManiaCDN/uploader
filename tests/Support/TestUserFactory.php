<?php

namespace App\Tests\Support;

use App\Entity\ManiaplanetUser;

class TestUserFactory
{
    public static function create(
        string $login = 'testuser',
        string $email = 'test@example.com',
        string $role = 'ROLE_USER',
        bool $emailSendApprovalNotification = true
    ): ManiaplanetUser {
        $user = new ManiaplanetUser();
        $user->setLogin($login);
        $user->setNickname($login);
        $user->setEmail($email);
        $user->setRole($role);
        $user->setEmailSendApprovalNotification($emailSendApprovalNotification);

        return $user;
    }
}
