<?php

namespace App\DataFixtures;

use App\Entity\Oeuvre;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OeuvreFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Création d'un utilisateur pour l'artiste, vous pouvez créer plus d'utilisateurs si nécessaire
        $artiste = new User();
        $artiste->setEmail($faker->email);
        $artiste->setPassword(password_hash('password', PASSWORD_BCRYPT)); // Assurez-vous que le mot de passe est correctement haché
        $artiste->setRoles(['ROLE_USER']);
        $manager->persist($artiste);

        for ($i = 0; $i < 10; $i++) {
            $oeuvre = new Oeuvre();
            $oeuvre->setTitre($faker->sentence);
            $oeuvre->setArtiste("mimi".$i); // Associe l'artiste à l'œuvre
            $oeuvre->setDate($faker->dateTimeBetween('-1 year', 'now'));
            $oeuvre->setType($faker->word);
            $oeuvre->setTechnique($faker->word);
            $oeuvre->setLieuCreation($faker->city);
            $oeuvre->setDimensions($faker->word);
            $oeuvre->setMouvement($faker->word);
            $oeuvre->setCollection($faker->word);
            $oeuvre->setDescription($faker->paragraph);
            $oeuvre->setImage($faker->imageUrl(800, 600, 'art', true)); // Utilisation d'une URL d'image fictive

            $manager->persist($oeuvre);
        }

        $manager->flush();
    }
}
