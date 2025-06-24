<?php
namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
            
        // Créer un utilisateur de test
        $testUser = new User();
        $testUser->setUsername('gameuser');
        $testUser->setEmail('gameuser@example.com');
        $testUser->setPassword('$2y$13$A8.rRkpBONLLtAXc9jv80OWy52WewhyMIgfgEZf5IKfMpEgrTFysi'); // 'password123'
        $testUser->setWallet(1000);
        $testUser->setCreationDate(new \DateTime());
        $testUser->setLastUpdateDate(new \DateTime());
        
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();
        
        // Authentification pour obtenir le token JWT
        $this->client->request(
            'POST',
            '/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'gameuser',
                'password' => 'password123'
            ])
        );
        
        $this->token = json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    public function testCreateGame(): void
    {
        $this->client->request(
            'POST',
            '/game',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('uuid', $responseContent);
        $this->assertArrayHasKey('creationDate', $responseContent);
        $this->assertArrayHasKey('finished', $responseContent);
        $this->assertFalse($responseContent['finished']);
        
        // Vérifier que le jeu a bien été créé en base
        $gameRepository = $this->entityManager->getRepository(Game::class);
        $game = $gameRepository->findOneBy(['uuid' => $responseContent['uuid']]);
        $this->assertNotNull($game);
    }
    
    public function testGetGame(): void
    {
        // Créer une partie pour l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'gameuser']);
        
        $game = new Game();
        $game->setUser($user);
        $game->setCreationDate(new \DateTime());
        $game->setFinished(false);
        
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        
        // Récupérer cette partie via l'API
        $this->client->request(
            'GET',
            '/game/' . $game->getUuid(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals($game->getUuid(), $responseContent['uuid']);
        $this->assertFalse($responseContent['finished']);
    }
    
    public function testDeleteGame(): void
    {
        // Créer une partie pour l'utilisateur
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'gameuser']);
        
        $game = new Game();
        $game->setUser($user);
        $game->setCreationDate(new \DateTime());
        $game->setFinished(false);
        
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        
        // Supprimer cette partie via l'API
        $this->client->request(
            'DELETE',
            '/game/' . $game->getUuid(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );
        
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        
        // Vérifier que la partie a bien été supprimée
        $gameRepository = $this->entityManager->getRepository(Game::class);
        $deletedGame = $gameRepository->findOneBy(['uuid' => $game->getUuid()]);
        $this->assertNull($deletedGame);
    }
}