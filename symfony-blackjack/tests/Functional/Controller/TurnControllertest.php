<?php
namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\Turn;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TurnControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $token;
    private $game;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
            
        // Créer un utilisateur de test
        $testUser = new User();
        $testUser->setUsername('turnuser');
        $testUser->setEmail('turnuser@example.com');
        $testUser->setPassword('$2y$13$A8.rRkpBONLLtAXc9jv80OWy52WewhyMIgfgEZf5IKfMpEgrTFysi'); // 'password123'
        $testUser->setWallet(1000);
        $testUser->setCreationDate(new \DateTime());
        $testUser->setLastUpdateDate(new \DateTime());
        
        $this->entityManager->persist($testUser);
        
        // Créer une partie pour cet utilisateur
        $game = new Game();
        $game->setUser($testUser);
        $game->setCreationDate(new \DateTime());
        $game->setFinished(false);
        
        $this->entityManager->persist($game);
        $this->entityManager->flush();
        
        $this->game = $game;
        
        // Authentification pour obtenir le token JWT
        $this->client->request(
            'POST',
            '/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'turnuser',
                'password' => 'password123'
            ])
        );
        
        $this->token = json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    public function testCreateTurn(): void
    {
        $this->client->request(
            'POST',
            '/game/' . $this->game->getUuid() . '/turn',
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
        
        // Vérifier que le tour a bien été créé en base
        $turnRepository = $this->entityManager->getRepository(Turn::class);
        $turn = $turnRepository->findOneBy(['uuid' => $responseContent['uuid']]);
        $this->assertNotNull($turn);
    }
    
    public function testWageTurn(): void
    {
        // Créer un tour pour la partie
        $turn = new Turn();
        $turn->setGame($this->game);
        $turn->setUser($this->game->getUser());
        $turn->setCreationDate(new \DateTime());
        $turn->setFinished(false);
        
        $this->entityManager->persist($turn);
        $this->entityManager->flush();
        
        // Miser sur ce tour
        $this->client->request(
            'PATCH',
            '/turn/' . $turn->getUuid() . '/wage',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ],
            json_encode(['amount' => 100])
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('userCards', $responseContent);
        $this->assertArrayHasKey('dealerCards', $responseContent);
        $this->assertCount(2, $responseContent['userCards']);
        $this->assertCount(1, $responseContent['dealerCards']);
        
        // Vérifier que la mise a bien été enregistrée
        $this->entityManager->refresh($turn);
        $this->assertEquals(100, $turn->getWage());
    }
    
    public function testHitTurn(): void
    {
        // Créer un tour avec des cartes déjà distribuées
        $turn = new Turn();
        $turn->setGame($this->game);
        $turn->setUser($this->game->getUser());
        $turn->setCreationDate(new \DateTime());
        $turn->setFinished(false);
        $turn->setWage(100);
        $turn->setUserCards([
            ['value' => '7', 'suit' => 'hearts'],
            ['value' => '6', 'suit' => 'clubs']
        ]);
        $turn->setDealerCards([
            ['value' => '10', 'suit' => 'diamonds']
        ]);
        
        $this->entityManager->persist($turn);
        $this->entityManager->flush();
        
        // Tirer une carte
        $this->client->request(
            'PATCH',
            '/turn/' . $turn->getUuid() . '/hit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('userCards', $responseContent);
        $this->assertCount(3, $responseContent['userCards']); // 2 cartes initiales + 1 piochée
        $this->assertArrayHasKey('userScore', $responseContent);
    }
    
    public function testStandTurn(): void
    {
        // Créer un tour avec des cartes déjà distribuées
        $turn = new Turn();
        $turn->setGame($this->game);
        $turn->setUser($this->game->getUser());
        $turn->setCreationDate(new \DateTime());
        $turn->setFinished(false);
        $turn->setWage(100);
        $turn->setUserCards([
            ['value' => 'king', 'suit' => 'hearts'],
            ['value' => '8', 'suit' => 'clubs']
        ]);
        $turn->setDealerCards([
            ['value' => '9', 'suit' => 'diamonds']
        ]);
        
        $this->entityManager->persist($turn);
        $this->entityManager->flush();
        
        // Se coucher
        $this->client->request(
            'PATCH',
            '/turn/' . $turn->getUuid() . '/stand',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('dealerCards', $responseContent);
        $this->assertCount(2, $responseContent['dealerCards']); // Au moins 1 carte supplémentaire
        $this->assertArrayHasKey('userScore', $responseContent);
        $this->assertArrayHasKey('dealerScore', $responseContent);
        $this->assertArrayHasKey('result', $responseContent);
        
        // Vérifier que le tour est terminé
        $this->entityManager->refresh($turn);
        $this->assertTrue($turn->isFinished());
    }
}