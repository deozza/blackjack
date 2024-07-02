<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\TurnRepository;
use App\Repository\UserRepository;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class GameServiceTest extends TestCase
{
    private $gameService;

    protected function setUp(): void
    {
        $gameRepository = $this->createMock(GameRepository::class);
        $turnRepository = $this->createMock(TurnRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $this->gameService = new GameService($gameRepository, $turnRepository, $entityManager, $logger, $formFactory);
    }

    public function testStartNewGame()
    {
        $user = new User();
        $game = $this->gameService->startNewGame($user);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($user, $game->getUser());
        $this->assertNotEmpty($game->getId());
    }
}
