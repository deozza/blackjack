<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
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

    public function testCreateGameAsAuthenticatedUser(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('playing', $data['status']);
    }

    public function testCreateGameUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/game');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetListOfGamesAsAdmin(): void
    {
        $client = static::createClient();
        
        $adminToken = $this->getAdminToken($client);
        
        $client->request('GET', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetListOfGamesAsRegularUser(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        
        $client->request('GET', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetListOfGamesWithPagination(): void
    {
        $client = static::createClient();
        
        $adminToken = $this->getAdminToken($client);
        
        $client->request('GET', '/game?limit=5&page=0', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetListOfGamesUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/game');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetGameAsOwner(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $client->request('GET', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($gameId, $data['id']);
    }

    public function testGetGameAsAdmin(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client);
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $adminToken = $this->getAdminToken($client);
        $client->request('GET', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetGameNotOwnerAndNotAdmin(): void
    {
        $client = static::createClient();
        
        $firstUserToken = $this->createUserAndGetToken($client, 'user1', 'user1@example.com');
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $firstUserToken
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $secondUserToken = $this->createUserAndGetToken($client, 'user2', 'user2@example.com');
        $client->request('GET', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $secondUserToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetGameNotFound(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/game/non-existent-id', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetGameUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/game/some-id');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteGameAsOwner(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $client->request('DELETE', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteGameAsAdmin(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client);
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $adminToken = $this->getAdminToken($client);
        $client->request('DELETE', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteGameNotOwnerAndNotAdmin(): void
    {
        $client = static::createClient();
        
        $firstUserToken = $this->createUserAndGetToken($client, 'user1', 'user1@example.com');
        $client->request('POST', '/game', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $firstUserToken
        ]);
        
        $gameData = json_decode($client->getResponse()->getContent(), true);
        $gameId = $gameData['id'];
        
        $secondUserToken = $this->createUserAndGetToken($client, 'user2', 'user2@example.com');
        $client->request('DELETE', '/game/' . $gameId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $secondUserToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteGameNotFound(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('DELETE', '/game/non-existent-id', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteGameUnauthenticated(): void
    {
        $client = static::createClient();
        
        $client->request('DELETE', '/game/some-id');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function getAdminToken($client): string
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'admin'
        ];
        
        $client->request('POST', '/login_check', [], [], [], json_encode($credentials));
        $data = json_decode($client->getResponse()->getContent(), true);
        
        return $data['token'];
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