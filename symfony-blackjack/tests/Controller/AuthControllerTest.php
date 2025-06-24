<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->client->request('POST', '/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin'
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request('POST', '/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'username' => 'wronguser',
            'password' => 'wrongpassword'
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
        $this->assertEquals('Invalid credentials.', $data['message']);
    }

    public function testLoginWithEmptyCredentials(): void
    {
        $this->client->request('POST', '/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid JSON', $data['detail']);
    }

    public function testLoginWithMissingPassword(): void
    {
        $this->client->request('POST', '/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['username' => 'admin']));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('password', $data['detail']);
    }

    public function testLoginWithInvalidJson(): void
    {
        $this->client->request('POST', '/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid json');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Invalid JSON', $data['detail']);
    }

    public function testLoginWithGetMethod(): void
    {
        $this->client->request('GET', '/login_check');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
} 