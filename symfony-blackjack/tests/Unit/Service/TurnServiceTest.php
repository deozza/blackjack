<?php


namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Entity\Game;
use App\Entity\Hand;
use App\Entity\Turn;
use App\Entity\User;
use App\Repository\TurnRepository;
use App\Service\HandService;
use App\Service\TurnService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class TurnServiceTest extends TestCase
{
    private TurnService $turnService;
    private $turnRepository;
    private $formFactory;
    private $em;
    private $handService;
    private $form;

    protected function setUp(): void
    {
        $this->turnRepository = $this->createMock(TurnRepository::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->handService = $this->createMock(HandService::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->turnService = new TurnService(
            $this->turnRepository,
            $this->formFactory,
            $this->em,
            $this->handService
        );
    }

    public function testCreateNewTurnSuccess(): void
    {
        $game = new Game();
        $game->setStatus('created');
        
        // Modifier cette partie pour simuler correctement le comportement de TurnService
        $this->turnRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function(Turn $turn) use ($game) {
                // Vérifier que le deck n'est pas null et est un tableau
                return $turn->getGame() === $game && 
                       $turn->getStatus() === 'waging' &&
                       is_array($turn->getDeck());
            }), true);
        
        [$turn, $error] = $this->turnService->createNewTurn($game);
        
        $this->assertInstanceOf(Turn::class, $turn);
        $this->assertNull($error);
        $this->assertEquals($game, $turn->getGame());
        $this->assertEquals('waging', $turn->getStatus());
        $this->assertIsArray($turn->getDeck());
    }

    public function testCreateNewTurnWithInvalidGameStatus(): void
    {
        $game = new Game();
        $game->setStatus('finished');
        
        [$turn, $error] = $this->turnService->createNewTurn($game);
        
        $this->assertNull($turn);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(409, $error->getCode());
        $this->assertEquals('The game has not started', $error->getMessage());
    }

    public function testWageTurnSuccess(): void
    {
        $user = new User();
        // Utiliser la réflexion pour définir l'ID
        $reflectionUser = new \ReflectionClass($user);
        $idPropertyUser = $reflectionUser->getProperty('id');
        $idPropertyUser->setAccessible(true);
        $idPropertyUser->setValue($user, '123');
        
        $user->setWallet(1000);
        
        $game = new Game();
        $game->setUser($user);
        
        $turn = new Turn();
        // Utiliser la réflexion pour définir l'ID
        $reflectionTurn = new \ReflectionClass($turn);
        $idPropertyTurn = $reflectionTurn->getProperty('id');
        $idPropertyTurn->setAccessible(true);
        $idPropertyTurn->setValue($turn, '456');
        
        $turn->setGame($game);
        $turn->setStatus('waging');
        // Initialiser un deck valide
        $turn->setDeck([new Card('clubs', '2')]);
        
        $this->turnRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '456'])
            ->willReturn($turn);
            
        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);
            
        $this->form->expects($this->once())
            ->method('submit')
            ->with(['wager' => 100]);
            
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        // Mock pour les méthodes d'initialisation du tour
        $playerHand = new Hand();
        $dealerHand = new Hand();
        
        $this->handService->expects($this->exactly(2))
            ->method('calculateScore')
            ->willReturnOnConsecutiveCalls(
                [$playerHand, null],
                [$dealerHand, null]
            );
            
        // Mock pour la méthode save
        $this->turnRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Turn::class), true);
        
        $data = ['wager' => 100];
        [$resultTurn, $error] = $this->turnService->wageTurn('456', $user, $data);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals(100, $resultTurn->getWager());
        $this->assertEquals(900, $user->getWallet()); // 1000 - 100
    }

    public function testHitTurnSuccess(): void
    {
        $user = new User();
        // Utiliser la réflexion pour définir l'ID
        $reflectionUser = new \ReflectionClass($user);
        $idPropertyUser = $reflectionUser->getProperty('id');
        $idPropertyUser->setAccessible(true);
        $idPropertyUser->setValue($user, '123');
        
        $game = new Game();
        $game->setUser($user);
        
        $turn = new Turn();
        // Utiliser la réflexion pour définir l'ID
        $reflectionTurn = new \ReflectionClass($turn);
        $idPropertyTurn = $reflectionTurn->getProperty('id');
        $idPropertyTurn->setAccessible(true);
        $idPropertyTurn->setValue($turn, '456');
        
        $turn->setGame($game);
        $turn->setStatus('playing');
        
        $playerHand = new Hand();
        $turn->setPlayerHand($playerHand);
        
        $this->turnRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '456'])
            ->willReturn($turn);
        
        // Set up deck with proper Card objects
        $card = new Card('hearts', '10');
        $turn->setDeck([$card]);
        
        $updatedHand = new Hand();
        $updatedHand->setScore(15);
        $updatedHand->setIsBlackjack(false);
        $updatedHand->setIsBusted(false);
        
        $this->handService->expects($this->once())
            ->method('calculateScore')
            ->willReturn([$updatedHand, null]);
        
        $this->turnRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Turn::class), true);
        
        [$resultTurn, $error] = $this->turnService->hitTurn('456', $user);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals($updatedHand, $resultTurn->getPlayerHand());
    }

    public function testStandTurnSuccess(): void
    {
        $user = new User();
        // Utiliser la réflexion pour définir l'ID
        $reflectionUser = new \ReflectionClass($user);
        $idPropertyUser = $reflectionUser->getProperty('id');
        $idPropertyUser->setAccessible(true);
        $idPropertyUser->setValue($user, '123');
        
        $game = new Game();
        $game->setUser($user);
        
        $turn = new Turn();
        // Utiliser la réflexion pour définir l'ID
        $reflectionTurn = new \ReflectionClass($turn);
        $idPropertyTurn = $reflectionTurn->getProperty('id');
        $idPropertyTurn->setAccessible(true);
        $idPropertyTurn->setValue($turn, '456');
        
        $turn->setGame($game);
        $turn->setStatus('playing');
        
        $this->turnRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => '456'])
            ->willReturn($turn);
        
        $this->turnRepository->expects($this->once())
            ->method('save')
            ->with($turn, true);
        
        [$resultTurn, $error] = $this->turnService->standTurn('456', $user);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals('dealer', $resultTurn->getStatus());
    }

    public function testDealerAutoDrawSuccess(): void
    {
        $turn = new Turn();
        $turn->setStatus('dealer');
        
        $dealerHand = new Hand();
        $dealerHand->setScore(15); // Moins de 17, donc piochera
        $turn->setDealerHand($dealerHand);
        
        // Set up deck with proper Card objects
        $card = new Card('hearts', '5');
        $turn->setDeck([$card]);
        
        $updatedHand = new Hand();
        $updatedHand->setScore(20); // 15 + 5
        $updatedHand->setIsBlackjack(false);
        $updatedHand->setIsBusted(false);
        
        $this->handService->expects($this->once())
            ->method('calculateScore')
            ->willReturn([$updatedHand, null]);
        
        $this->turnRepository->expects($this->once())
            ->method('save')
            ->with($turn, true);
        
        [$resultTurn, $error] = $this->turnService->dealerAutoDraw($turn);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals('distributeGains', $resultTurn->getStatus());
        $this->assertEquals($updatedHand, $resultTurn->getDealerHand());
    }

    public function testDistributeGainsPlayerWins(): void
    {
        $user = new User();
        $user->setWallet(1000);
        
        $game = new Game();
        $game->setUser($user);
        
        $turn = new Turn();
        $turn->setGame($game);
        $turn->setStatus('distributeGains');
        $turn->setWager(100);
        
        $playerHand = new Hand();
        $playerHand->setScore(20);
        $playerHand->setIsBlackjack(false);
        $playerHand->setIsBusted(false);
        $turn->setPlayerHand($playerHand);
        
        $dealerHand = new Hand();
        $dealerHand->setScore(18);
        $dealerHand->setIsBlackjack(false);
        $dealerHand->setIsBusted(false);
        $turn->setDealerHand($dealerHand);
        
        // Utiliser des mocks consécutifs
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);
        $turnRepository = $this->createMock(\App\Repository\TurnRepository::class);
        
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [User::class],
                [Turn::class]
            )
            ->willReturnOnConsecutiveCalls(
                $userRepository,
                $turnRepository
            );
        
        $userRepository->expects($this->once())
            ->method('save')
            ->with($user, true);
        
        $turnRepository->expects($this->once())
            ->method('save')
            ->with($turn, true);
        
        [$resultTurn, $error] = $this->turnService->distributeGains($turn);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals('won', $resultTurn->getStatus());
        $this->assertEquals(1200, $user->getWallet()); // 1000 + 100 (mise) + 100 (gains)
    }

    public function testDistributeGainsPlayerBlackjack(): void
    {
        $user = new User();
        $user->setWallet(1000);
        
        $game = new Game();
        $game->setUser($user);
        
        $turn = new Turn();
        $turn->setGame($game);
        $turn->setStatus('distributeGains');
        $turn->setWager(100);
        
        $playerHand = new Hand();
        $playerHand->setScore(21);
        $playerHand->setIsBlackjack(true);
        $playerHand->setIsBusted(false);
        $turn->setPlayerHand($playerHand);
        
        $dealerHand = new Hand();
        $dealerHand->setScore(19);
        $dealerHand->setIsBlackjack(false);
        $dealerHand->setIsBusted(false);
        $turn->setDealerHand($dealerHand);
        
        // Utiliser des mocks consécutifs
        $userRepository = $this->createMock(\App\Repository\UserRepository::class);
        $turnRepository = $this->createMock(\App\Repository\TurnRepository::class);
        
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [User::class],
                [Turn::class]
            )
            ->willReturnOnConsecutiveCalls(
                $userRepository,
                $turnRepository
            );
        
        $userRepository->expects($this->once())
            ->method('save')
            ->with($user, true);
        
        $turnRepository->expects($this->once())
            ->method('save')
            ->with($turn, true);
        
        [$resultTurn, $error] = $this->turnService->distributeGains($turn);
        
        $this->assertInstanceOf(Turn::class, $resultTurn);
        $this->assertNull($error);
        $this->assertEquals('won', $resultTurn->getStatus());
        $this->assertEquals(1300, $user->getWallet()); // 1000 + 100 (mise) + 200 (gains doublés pour blackjack)
    }
}