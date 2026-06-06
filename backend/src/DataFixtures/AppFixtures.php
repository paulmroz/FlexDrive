<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\CarFactory;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        CarFactory::createOne(attributes: [
            'brand' => 'Tesla',
            'model' => 'Model Y',
            'pricePerDay' => 6500,
            'available' => true,
        ]);

        CarFactory::createOne(attributes: [
            'brand' => 'Porsche',
            'model' => '911 GT3',
            'pricePerDay' => 18000,
            'available' => true,
        ]);

        CarFactory::createOne(attributes: [
            'brand' => 'Volkswagen',
            'model' => 'Golf GTI',
            'pricePerDay' => 3500,
            'available' => true,
        ]);

        CarFactory::createOne(attributes: [
            'brand' => 'Audi',
            'model' => 'RS6 Avant',
            'pricePerDay' => 12000,
            'available' => true,
        ]);

        CarFactory::createOne(attributes: [
            'brand' => 'BMW',
            'model' => 'M3 Competition',
            'pricePerDay' => 11000,
            'available' => true,
        ]);

        $adminId = UserId::generate();
        $adminEmail = new Email(value: 'admin@driveagency.com');
        $tempAdmin = new User(
            id: $adminId,
            email: $adminEmail,
            password: '',
            roles: ['ROLE_ADMIN']
        );
        $hashedAdminPassword = $this->passwordHasher->hashPassword(
            user: new SecurityUser(user: $tempAdmin),
            plainPassword: 'adminpassword'
        );
        $admin = new User(
            id: $adminId,
            email: $adminEmail,
            password: $hashedAdminPassword,
            roles: ['ROLE_ADMIN']
        );
        $manager->persist($admin);

        $userId = UserId::generate();
        $userEmail = new Email(value: 'user@driveagency.com');
        $tempUser = new User(
            id: $userId,
            email: $userEmail,
            password: '',
            roles: ['ROLE_USER']
        );
        $hashedUserPassword = $this->passwordHasher->hashPassword(
            user: new SecurityUser(user: $tempUser),
            plainPassword: 'userpassword'
        );
        $user = new User(
            id: $userId,
            email: $userEmail,
            password: $hashedUserPassword,
            roles: ['ROLE_USER']
        );
        $manager->persist($user);

        $manager->flush();
    }
}
