<?php

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Entity\Turn;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Service\GameService;
use App\Service\TurnService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class GameServiceTest extends TestCase
{
    private GameService $gameService;
    private $gameRepository;
    private $formFactory;
    private $em;
    private $turnService;

    protected function setUp(): void
    {
        $this->gameRepository = $this->createMock(GameRepository::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->turnService = $this->createMock(TurnService::class);

        $this->gameService = new GameService(
            $this->gameRepository,
            $this->formFactory,
            $this->em,
            $this->turnService
        );
    }

    public function testGetPaginatedGameList(): void
    {
        $games = [new Game(), new Game()];
        
        $this->gameRepository->expects($this->once())
            ->method('findBy')
            ->with([], [], 10, 0)
            ->willReturn($games);
        
        [$result, $error] = $this->gameService->getPaginatedGameList(10, 0);
        
        $this->assertSame($games, $result);
        $this->assertNull($error);
    }

    public function testGetPaginatedGameListForUser(): void
    {
        $userId = '123';
        $games = [new Game(), new Game()];
        
        $this->gameRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $userId], [], 10, 0)
            ->willReturn($games);
        
        [$result, $error] = $this->gameService->getPaginatedGameList(10, 0, $userId);
        
        $this->assertSame($games, $result);
        $this->assertNull($error);
    }

    public function testCreateGameSuccess(): void
    {
        $user = new User();
        $user->setWallet(1000);
        
        $this->gameRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Game::class), true);
        
        [$game, $error] = $this->gameService->createGame($user);
        
        $this->assertInstanceOf(Game::class, $game);
        $this->assertNull($error);
        $this->assertEquals($user, $game->getUser());
        $this->assertEquals('created', $game->getStatus());
    }

    public function testCreateGameNotEnoughMoney(): void
    {
        $user = new User();
        $user->setWallet(5); // Moins que les 10 requis
        
        [$game, $error] = $this->gameService->createGame($user);
        
        $this->assertNull($game);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(400, $error->getCode());
        $this->assertEquals('Not enough money to create a game', $error->getMessage());
    }

    public function testGenerateNewGame(): void
    {
        $user = new User();
        
        $game = $this->gameService->generateNewGame($user);
        
        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($user, $game->getUser());
        $this->assertEquals('created', $game->getStatus());
        $this->assertInstanceOf(\DateTime::class, $game->getDateCreation());
        $this->assertInstanceOf(\DateTime::class, $game->getLastUpdateDate());
    }

    public function testInitializeGameSuccess(): void
    {
        $game = new Game();
        $turn = new Turn();
        
        $this->turnService->expects($this->once())
            ->method('createNewTurn')
            ->with($game)
            ->willReturn([$turn, null]);
        
        $this->gameRepository->expects($this->once())
            ->method('save')
            ->with($game, true);
        
        [$result, $error] = $this->gameService->initializeGame($game);
        
        $this->assertSame($game, $result);
        $this->assertNull($error);
        $this->assertEquals('playing', $game->getStatus());
    }

    public function testInitializeGameError(): void
    {
        $game = new Game();
        $error = new \Error('Turn creation failed', 400);
        
        $this->turnService->expects($this->once())
            ->method('createNewTurn')
            ->with($game)
            ->willReturn([null, $error]);
        
        [$result, $returnedError] = $this->gameService->initializeGame($game);
        
        $this->assertNull($result);
        $this->assertSame($error, $returnedError);
    }

    public function testGetGameById(): void
    {
        $gameId = '123';
        $user = new User();
        
        // Utiliser la réflexion pour définir l'ID
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, '456');
        
        $game = new Game();
        $game->setUser($user);
        
        $this->gameRepository->expects($this->once())
            ->method('find')
            ->with($gameId)
            ->willReturn($game);
        
        [$result, $error] = $this->gameService->getGame($gameId, $user);
        
        $this->assertSame($game, $result);
        $this->assertNull($error);
    }

    public function testGetGameNotFound(): void
    {
        $gameId = 'non-existent';
        $user = new User();
        
        $this->gameRepository->expects($this->once())
            ->method('find')
            ->with($gameId)
            ->willReturn(null);
        
        [$result, $error] = $this->gameService->getGame($gameId, $user);
        
        $this->assertNull($result);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(404, $error->getCode());
        $this->assertEquals('Game not found', $error->getMessage());
    }

    public function testGetGameUnauthorizedAccess(): void
    {
        $gameId = '123';
        $gameUser = new User();
        
        // Utiliser la réflexion pour définir l'ID du premier utilisateur
        $reflection1 = new \ReflectionClass($gameUser);
        $property1 = $reflection1->getProperty('id');
        $property1->setAccessible(true);
        $property1->setValue($gameUser, '456');
        
        $user = new User();
        // Utiliser la réflexion pour définir l'ID du deuxième utilisateur
        $reflection2 = new \ReflectionClass($user);
        $property2 = $reflection2->getProperty('id');
        $property2->setAccessible(true);
        $property2->setValue($user, '789');
        
        $game = new Game();
        $game->setUser($gameUser);
        
        $this->gameRepository->expects($this->once())
            ->method('find')
            ->with($gameId)
            ->willReturn($game);
        
        [$result, $error] = $this->gameService->getGame($gameId, $user);
        
        $this->assertNull($result);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(403, $error->getCode());
        $this->assertEquals('You are not allowed to access this game', $error->getMessage());
    }
}