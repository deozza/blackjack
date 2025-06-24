<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractControllerTest extends WebTestCase
{
    protected $client;
    protected ?string $authToken = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function getAuthToken(): string
    {
        if ($this->authToken === null) {
            $this->client->request('POST', '/login_check', [], [], [
                'CONTENT_TYPE' => 'application/json'
            ], json_encode([
                'username' => 'admin',
                'password' => 'admin'
            ]));

            $response = $this->client->getResponse();
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $this->authToken = $data['token'];
            } else {
                $this->fail('Failed to authenticate admin user. Status: ' . $response->getStatusCode());
            }
        }

        return $this->authToken;
    }

    protected function makeAuthenticatedRequest(string $method, string $uri, array $data = []): void
    {
        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getAuthToken(),
            'CONTENT_TYPE' => 'application/json'
        ];

        $content = empty($data) ? null : json_encode($data);
        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    protected function makeUnauthenticatedRequest(string $method, string $uri, array $data = []): void
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json'
        ];

        $content = empty($data) ? null : json_encode($data);
        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    protected function getResponseData(): array
    {
        $content = $this->client->getResponse()->getContent();
        if ($content === false || $content === '') {
            return [];
        }
        
        $decoded = json_decode($content, true);
        
        if (!is_array($decoded)) {
            return [];
        }
        
        return $decoded;
    }

    protected function assertJsonResponse(int $expectedStatusCode): array
    {
        $response = $this->client->getResponse();
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        return $this->getResponseData();
    }

    protected function createTestUser(): array
    {
        $userData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password123'
        ];

        $this->makeUnauthenticatedRequest('POST', '/user', $userData);
        return $this->getResponseData();
    }

    protected function createTestGame(): array
    {
        $this->makeAuthenticatedRequest('POST', '/game');
        return $this->getResponseData();
    }

    protected function createTestTurn(string $gameId): array
    {
        $this->makeAuthenticatedRequest('GET', '/game/' . $gameId);
        $gameData = $this->getResponseData();
        
        if (isset($gameData['turns']) && !empty($gameData['turns'])) {
            return $gameData['turns'][0];
        }
        
        $this->makeAuthenticatedRequest('POST', '/game/' . $gameId . '/turn');
        return $this->getResponseData();
    }
} 