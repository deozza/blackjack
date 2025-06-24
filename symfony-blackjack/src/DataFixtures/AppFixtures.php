<?php
// filepath: /Users/zahidikays/Desktop/4AWD/S2/Test_QA/Eval/blackjack/symfony-blackjack/src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\Turn;
use App\Entity\Card;
use App\Entity\Hand;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des utilisateurs
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));
        $admin->setWallet(5000);
        $admin->setCreationDate(new \DateTime());
        $admin->setLastUpdateDate(new \DateTime());
        $manager->persist($admin);
        
        $player = new User();
        $player->setUsername('player');
        $player->setEmail('player@example.com');
        $player->setRoles(['ROLE_USER']);
        $player->setPassword($this->passwordHasher->hashPassword($player, 'player'));
        $player->setWallet(1000);
        $player->setCreationDate(new \DateTime());
        $player->setLastUpdateDate(new \DateTime());
        $manager->persist($player);
        
        // Création des parties
        $activeGame = new Game();
        $activeGame->setUser($player);
        $activeGame->setStatus('active');
        $activeGame->setDateCreation(new \DateTime());
        $activeGame->setLastUpdateDate(new \DateTime());
        $manager->persist($activeGame);
        
        $finishedGame = new Game();
        $finishedGame->setUser($player);
        $finishedGame->setStatus('finished');
        $finishedGame->setDateCreation(new \DateTime('-1 day'));
        $finishedGame->setLastUpdateDate(new \DateTime('-1 day'));
        $manager->persist($finishedGame);
        
        // Création des tours
        $activeTurn = new Turn();
        $activeTurn->setGame($activeGame);
        $activeTurn->setStatus('active');
        $activeTurn->setCreationDate(new \DateTime());
        $activeTurn->setLastUpdateDate(new \DateTime());
        $activeTurn->setWager(100);
        
        // Création du deck
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'jack', 'queen', 'king', 'ace'];
        $deck = [];
        
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = new Card($suit, $value);
            }
        }
        // Mélanger le deck
        shuffle($deck);
        $activeTurn->setDeck($deck);
        
        // Création des mains
        $playerHand = new Hand();
        $playerHand->setCards([
            new Card('hearts', '10'),
            new Card('clubs', '7')
        ]);
        $playerHand->setScore(17);
        $playerHand->setIsBlackjack(false);
        $playerHand->setIsBusted(false);
        
        $dealerHand = new Hand();
        $dealerHand->setCards([
            new Card('diamonds', '9')
        ]);
        $dealerHand->setScore(9);
        $dealerHand->setIsBlackjack(false);
        $dealerHand->setIsBusted(false);
        
        $activeTurn->setPlayerHand($playerHand);
        $activeTurn->setDealerHand($dealerHand);
        $manager->persist($activeTurn);
        
        // Création d'un tour terminé
        $finishedTurn = new Turn();
        $finishedTurn->setGame($finishedGame);
        $finishedTurn->setStatus('finished');
        $finishedTurn->setCreationDate(new \DateTime('-1 day'));
        $finishedTurn->setLastUpdateDate(new \DateTime('-1 day'));
        $finishedTurn->setWager(200);
        
        // Mains pour le tour terminé
        $playerFinishedHand = new Hand();
        $playerFinishedHand->setCards([
            new Card('hearts', 'ace'),
            new Card('clubs', 'king')
        ]);
        $playerFinishedHand->setScore(21);
        $playerFinishedHand->setIsBlackjack(true);
        $playerFinishedHand->setIsBusted(false);
        
        $dealerFinishedHand = new Hand();
        $dealerFinishedHand->setCards([
            new Card('diamonds', '10'),
            new Card('spades', '8')
        ]);
        $dealerFinishedHand->setScore(18);
        $dealerFinishedHand->setIsBlackjack(false);
        $dealerFinishedHand->setIsBusted(false);
        
        // Deck vide car partie terminée
        $finishedTurn->setDeck([]);
        $finishedTurn->setPlayerHand($playerFinishedHand);
        $finishedTurn->setDealerHand($dealerFinishedHand);
        $manager->persist($finishedTurn);
        
        // Ajout des relations bidirectionnelles
        $activeGame->addTurn($activeTurn);
        $finishedGame->addTurn($finishedTurn);
        $player->addGame($activeGame);
        $player->addGame($finishedGame);
        
        $manager->flush();
    }
}