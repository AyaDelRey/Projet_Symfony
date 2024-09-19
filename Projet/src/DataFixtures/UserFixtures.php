<?php

// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create a Faker instance
        $faker = Factory::create();

        // Generate 10 fake users
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            
            // Set a random email
            $user->setEmail($faker->unique()->safeEmail);

            // Set a random password (hashed)
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            // Set roles
            $user->setRoles(['ROLE_USER']);
            
            // Persist the user object
            $manager->persist($user);
        }

        // Save to the database
        $manager->flush();
    }
}
