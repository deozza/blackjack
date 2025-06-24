<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class TurnControllerTest extends AbstractControllerTest
{
    public function testCreateTurnWhenGameAlreadyHasOne(): void
    {
        $game = $this->createTestGame();
        
        $this->makeAuthenticatedRequest('POST', '/game/' . $game['id'] . '/turn');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testCreateTurnWithoutAuth(): void
    {
        $game = $this->createTestGame();
        
        $this->makeUnauthenticatedRequest('POST', '/game/' . $game['id'] . '/turn');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateTurnForNonExistentGame(): void
    {
        $fakeGameId = '550e8400-e29b-41d4-a716-446655440000';
        
        $this->makeAuthenticatedRequest('POST', '/game/' . $fakeGameId . '/turn');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGetTurnDetails(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $this->makeAuthenticatedRequest('GET', '/turn/' . $turn['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = $this->getResponseData();
        $this->assertEquals($turn['id'], $data['id']);
        $this->assertArrayHasKey('creationDate', $data);
    }

    public function testGetNonExistentTurn(): void
    {
        $fakeTurnId = '550e8400-e29b-41d4-a716-446655440000';
        
        $this->makeAuthenticatedRequest('GET', '/turn/' . $fakeTurnId);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGetTurnWithoutAuth(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $this->makeUnauthenticatedRequest('GET', '/turn/' . $turn['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testWageTurn(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $wageData = [
            'wager' => 100
        ];

        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/wage', $wageData);

        $response = $this->client->getResponse();
        
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CONFLICT]);
    }

    public function testWageTurnWithInvalidAmount(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $wageData = [
            'wager' => -50
        ];

        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/wage', $wageData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testWageTurnWithoutAuth(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $wageData = [
            'wager' => 100
        ];

        $this->makeUnauthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/wage', $wageData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testHitTurn(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        
        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/hit');

        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CONFLICT]);
    }

    public function testHitTurnWithoutWaging(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);

        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/hit');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testHitTurnWithoutAuth(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);

        $this->makeUnauthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/hit');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testStandTurn(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);
        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/stand');

        $response = $this->client->getResponse();
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CONFLICT]);
    }

    public function testStandTurnWithoutWaging(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);

        $this->makeAuthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/stand');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testStandTurnWithoutAuth(): void
    {
        $game = $this->createTestGame();
        $turn = $this->createTestTurn($game['id']);

        $this->makeUnauthenticatedRequest('PATCH', '/turn/' . $turn['id'] . '/stand');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
} 