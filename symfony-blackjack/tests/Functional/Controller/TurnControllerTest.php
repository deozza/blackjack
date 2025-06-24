<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TurnControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);

            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->restoreExceptionHandler();
    }

    public function testCreateTurnAsGameOwner(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        // Créer une partie
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        // Créer un tour pour cette partie
        $client->request('POST', '/game/' . $gameId . '/turn', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('waging', $data['status']);
    }

    public function testCreateTurnUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/game/some-game-id/turn');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateTurnNotOwner(): void
    {
        $client = static::createClient();
        
        $firstUserToken = $this->createUserAndGetToken($client, 'user1', 'user1@example.com');
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $firstUserToken
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $secondUserToken = $this->createUserAndGetToken($client, 'user2', 'user2@example.com');
        
        $client->request('POST', '/game/' . $gameId . '/turn', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $secondUserToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateTurnGameNotFound(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game/non-existent-id/turn', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetTurnAsParticipant(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $client->request('GET', '/turn/' . $turnId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($turnId, $data['id']);
        $this->assertArrayHasKey('status', $data);
    }

    public function testGetTurnUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/turn/some-turn-id');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetTurnNotParticipant(): void
    {
        $client = static::createClient();
        
        $firstUserToken = $this->createUserAndGetToken($client, 'user1', 'user1@example.com');
        $turnId = $this->createGameAndTurn($client, $firstUserToken);
        
        $secondUserToken = $this->createUserAndGetToken($client, 'user2', 'user2@example.com');
        
        $client->request('GET', '/turn/' . $turnId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $secondUserToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetTurnNotFound(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/turn/non-existent-id', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testWageTurnSuccess(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $wageData = [
            'wager' => 10
        ];
        
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('playing', $data['status']);
        $this->assertEquals(10, $data['wager']);
        $this->assertArrayHasKey('playerHand', $data);
        $this->assertArrayHasKey('dealerHand', $data);
    }

    public function testWageTurnUnauthenticated(): void
    {
        $client = static::createClient();
        
        $wageData = ['wager' => 10];
        
        $client->request('PATCH', '/turn/some-turn-id/wage', [], [], [], json_encode($wageData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testWageTurnInvalidWager(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $wageData = [
            'wager' => -5  
        ];
        
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testWageTurnInsufficientFunds(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $wageData = [
            'wager' => 10000  
        ];
        
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testHitTurnSuccess(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $wageData = ['wager' => 10];
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        
        $client->request('PATCH', '/turn/' . $turnId . '/hit', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('playerHand', $data);
        $this->assertGreaterThanOrEqual(3, count($data['playerHand']['cards']));
    }

    public function testHitTurnUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('PATCH', '/turn/some-turn-id/hit');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testHitTurnWrongStatus(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $client->request('PATCH', '/turn/' . $turnId . '/hit', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testStandTurnSuccess(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $wageData = ['wager' => 10];
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        
        $client->request('PATCH', '/turn/' . $turnId . '/stand', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertContains($data['status'], ['won', 'lost', 'draw']);
        $this->assertArrayHasKey('dealerHand', $data);
    }

    public function testStandTurnUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('PATCH', '/turn/some-turn-id/stand');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testStandTurnWrongStatus(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        $turnId = $this->createGameAndTurn($client, $token);
        
        $client->request('PATCH', '/turn/' . $turnId . '/stand', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testCompleteGameFlow(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $client->request('POST', '/game/' . $gameId . '/turn', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $turnData = json_decode($client->getResponse()->getContent(), true);
        $turnId = $turnData['id'];
        
        $wageData = ['wager' => 5];
        $client->request('PATCH', '/turn/' . $turnId . '/wage', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($wageData));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $client->request('GET', '/turn/' . $turnId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $turnData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('playing', $turnData['status']);
        
        $client->request('PATCH', '/turn/' . $turnId . '/stand', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $finalData = json_decode($client->getResponse()->getContent(), true);
        $this->assertContains($finalData['status'], ['won', 'lost', 'draw']);
    }

    private function createGameAndTurn($client, string $token): string
    {
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $client->request('POST', '/game/' . $gameId . '/turn', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $turnData = json_decode($client->getResponse()->getContent(), true);
        return $turnData['id'];
    }

    private function createUserAndGetToken($client, string $username = 'testuser', string $email = 'test@example.com'): string
    {
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => 'password123'
        ];
        
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        
        $credentials = [
            'username' => $username,
            'password' => 'password123'
        ];
        
        $client->request('POST', '/login_check', [], [], [], json_encode($credentials));
        $data = json_decode($client->getResponse()->getContent(), true);
        
        return $data['token'];
    }
}