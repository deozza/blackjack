<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class GameFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $users = $manager->getRepository(User::class)->findAll();

        if (empty($users)) {
            throw new \Exception('No users found. Please load User fixtures first.');
        }

        for ($i = 0; $i < 5; $i++) {
            $game = new Game();
            $game->setUser($faker->randomElement($users));
            $game->setStatus($faker->randomElement(['pending', 'in_progress', 'completed']));
            $game->setDateCreation($faker->dateTimeThisYear);
            $game->setLastUpdateDate($faker->dateTimeThisMonth);

            $manager->persist($game);
        }

        $manager->flush();
    }
}
