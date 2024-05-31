<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TurnControllerTest extends WebTestCase
{
    private $client;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->request('POST', '/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'admin',
            'password' => 'admin',
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->token = $data['token'];
    }

    public function testCreateTurn(): void
    {
        $this->client->request('POST', '/game/1/turn', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->token]);
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetTurn(): void
    {
        $this->client->request('GET', '/turn/1', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->token]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testWageTurn(): void
    {
        $this->client->request('PATCH', '/turn/1/wage', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->token], json_encode([
            'wage' => 100,
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testHitTurn(): void
    {
        $this->client->request('PATCH', '/turn/1/hit', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->token]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testStandTurn(): void
    {
        $this->client->request('PATCH', '/turn/1/stand', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->token]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }
}