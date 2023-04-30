<?php

namespace App\DataFixtures;

use App\Entity\ManiaplanetUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture {

    public function load(ObjectManager $manager)
    {
        $manager->persist($this->createUser());

        $manager->flush();
    }

    private function createUser(): ManiaplanetUser {
        $user = new ManiaplanetUser();
        $user->setEmail('test@example.com');
        $user->setNickname('test user');
        $user->setLogin('testuser');
        $user->setRole('ROLE_USER');
        $user->setEmailSendApprovalNotification(true);
        return $user;
    }

}
