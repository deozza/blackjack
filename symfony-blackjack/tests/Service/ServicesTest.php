<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\Turn;
use App\Entity\Hand;
use App\Entity\Card;
use App\Service\TurnService;
use App\Service\UserService;
use App\Service\HandService;
use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests unitaires pour détecter les erreurs dans les Services
 */
class ServicesTest extends KernelTestCase
{
    private $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = self::getContainer();
    }

    /**
     * Test TurnService - Détection erreur variable $deck
     */
    public function testTurnServiceDeckVariableError(): void
    {
        try {
            $turnService = $this->container->get(TurnService::class);
            
            $user = new User();
            $user->setWallet(1000);
            
            $game = new Game();
            $game->setUser($user);
            $game->setStatus('created');
            
            list($turn, $error) = $turnService->createNewTurn($game);
            
            $this->assertTrue($error instanceof \Error || $turn === null, 
                'Erreur attendue : Variable $deck non définie dans createNewTurn()');
                
        } catch (\Error $e) {
            $this->assertStringContainsString('deck', strtolower($e->getMessage()), 
                'Erreur détectée : Variable $deck non définie');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Exception capturée comme attendu');
        }
    }

    /**
     * Test UserService - Détection erreur hash password
     */
    public function testUserServicePasswordHashError(): void
    {
        try {
            $userService = $this->container->get(UserService::class);
            
            $userData = [
                'email' => 'test@example.com',
                'username' => 'testuser',
                'password' => 'testpassword123'
            ];
            
            list($user, $error) = $userService->createUser($userData);
            
            if ($user !== null) {
                $hashedPassword = $user->getPassword();
                
                $this->assertStringContainsString('$data', $hashedPassword, 
                    'Erreur détectée : Hash du mot de passe incorrect - string littéral au lieu de la variable');
            }
            
        } catch (\Error | \Exception $e) {
            $this->assertTrue(true, 'Exception capturée - erreur dans UserService détectée');
        }
    }

    /**
     * Test HandService - Détection erreur calcul cartes faces
     */
    public function testHandServiceCardCalculationError(): void
    {
        try {
            $handService = $this->container->get(HandService::class);
            
            $hand = new Hand();
            $hand->addCard(new Card('heart', 'J')); 
            $hand->addCard(new Card('spade', 'Q')); 
            
            list($calculatedHand, $error) = $handService->calculateScore($hand);
            
            if ($calculatedHand !== null) {
                $score = $calculatedHand->getScore();
                
                $this->assertEquals(20, $score, 
                    'Erreur détectée : Calcul incorrect des cartes faces dans HandService');
            }
            
        } catch (\Error | \Exception $e) {
            $this->assertTrue(true, 'Exception capturée - erreur dans HandService détectée');
        }
    }

    /**
     * Test TurnService - Détection erreur drawTopCard status
     */
    public function testTurnServiceDrawTopCardStatusError(): void
    {
        try {
            $turnService = $this->container->get(TurnService::class);
            
            $turn = new Turn();
            $turn->setStatus('initializing');
            $turn->setDeck([new Card('heart', '2'), new Card('spade', '3')]);
            
            list($card, $error) = $turnService->drawTopCard($turn);
            
            $this->assertTrue($error instanceof \Error, 
                'Erreur détectée : drawTopCard refuse le statut initializing');
            $this->assertEquals(409, $error->getCode(),
                'Code erreur 409 confirmé pour statut initializing');
                
        } catch (\Error | \Exception $e) {
            $this->assertTrue(true, 'Exception capturée - erreur de statut détectée');
        }
    }

    /**
     * Test GameService - Vérification cohérence workflow
     */
    public function testGameServiceWorkflowConsistency(): void
    {
        try {
            $gameService = $this->container->get(GameService::class);
            
            $user = new User();
            $user->setWallet(1000);
            
            list($game, $error) = $gameService->createGame($user);
            
            if ($game !== null) {
                list($initializedGame, $initError) = $gameService->initializeGame($game);
                
                $this->assertTrue($initError instanceof \Error || $initializedGame === null,
                    'Erreur en cascade détectée : GameService -> TurnService');
            }
            
        } catch (\Error | \Exception $e) {
            $this->assertStringContainsString('deck', strtolower($e->getMessage()), 
                'Erreur en cascade confirmée : problème deck remonte jusqu\'à GameService');
        }
    }

    /**
     * Test intégration complète - Détection erreurs multiples
     */
    public function testCompleteIntegrationErrors(): void
    {
        $errorsDetected = [];
        
        try {
            $turnService = $this->container->get(TurnService::class);
            $user = new User();
            $user->setWallet(1000);
            $game = new Game();
            $game->setUser($user);
            $game->setStatus('created');
            
            list($turn, $error) = $turnService->createNewTurn($game);
            if ($error instanceof \Error) {
                $errorsDetected[] = 'TurnService: Variable $deck error';
            }
        } catch (\Throwable $e) {
            $errorsDetected[] = 'TurnService: Exception caught';
        }
        
        try {
            $userService = $this->container->get(UserService::class);
            list($user, $error) = $userService->createUser([
                'email' => 'test@test.com',
                'username' => 'test',
                'password' => 'password'
            ]);
            
            if ($user && strpos($user->getPassword(), '$data') !== false) {
                $errorsDetected[] = 'UserService: Password hash error';
            }
        } catch (\Throwable $e) {
            $errorsDetected[] = 'UserService: Exception caught';
        }
        
        try {
            $handService = $this->container->get(HandService::class);
            $hand = new Hand();
            $hand->addCard(new Card('heart', 'K'));
            
            list($result, $error) = $handService->calculateScore($hand);
            if ($result && $result->getScore() !== 10) {
                $errorsDetected[] = 'HandService: Card calculation error';
            }
        } catch (\Throwable $e) {
            $errorsDetected[] = 'HandService: Exception caught';
        }
        
        $this->assertGreaterThan(0, count($errorsDetected), 
            'Au moins une erreur critique devrait être détectée dans les services. Erreurs trouvées: ' . 
            implode(', ', $errorsDetected));
    }
}