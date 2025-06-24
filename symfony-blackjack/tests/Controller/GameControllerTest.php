<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends AbstractControllerTest
{
    public function testCreateGame(): void
    {
        $this->makeAuthenticatedRequest('POST', '/game');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        
        $data = $this->getResponseData();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('dateCreation', $data);
        $this->assertArrayHasKey('status', $data);
    }

    public function testCreateGameWithoutAuth(): void
    {
        $this->makeUnauthenticatedRequest('POST', '/game');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetGameListAsAdmin(): void
    {
        $this->makeAuthenticatedRequest('GET', '/game');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = $this->getResponseData();
        $this->assertIsArray($data);
    }

    public function testGetGameListWithPagination(): void
    {
        $this->makeAuthenticatedRequest('GET', '/game?limit=5&page=0');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(5, count($data));
    }

    public function testGetGameDetails(): void
    {
        $game = $this->createTestGame();
        
        $this->makeAuthenticatedRequest('GET', '/game/' . $game['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = $this->getResponseData();
        $this->assertEquals($game['id'], $data['id']);
        $this->assertArrayHasKey('dateCreation', $data);
    }

    public function testGetNonExistentGame(): void
    {
        $fakeGameId = '550e8400-e29b-41d4-a716-446655440000';
        
        $this->makeAuthenticatedRequest('GET', '/game/' . $fakeGameId);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGetGameWithoutAuth(): void
    {
        $game = $this->createTestGame();
        
        $this->makeUnauthenticatedRequest('GET', '/game/' . $game['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testDeleteGame(): void
    {
        $game = $this->createTestGame();
        
        $this->makeAuthenticatedRequest('DELETE', '/game/' . $game['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeleteNonExistentGame(): void
    {
        $fakeGameId = '550e8400-e29b-41d4-a716-446655440000';
        
        $this->makeAuthenticatedRequest('DELETE', '/game/' . $fakeGameId);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testDeleteGameWithoutAuth(): void
    {
        $game = $this->createTestGame();
        
        $this->makeUnauthenticatedRequest('DELETE', '/game/' . $game['id']);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
} 