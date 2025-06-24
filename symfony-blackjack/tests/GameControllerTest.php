<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function testCreateGame(): void
    {
        $client = static::createClient();
        $client->request('POST', '/game', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer YOUR_VALID_JWT_TOKEN'
        ], json_encode([]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testGetGames(): void
    {
        $client = static::createClient();
        $client->request('GET', '/game', [], [], [
            'HTTP_Authorization' => 'Bearer YOUR_VALID_JWT_TOKEN'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testUnauthorizedGameAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/game');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTurnWithoutWager(): void
    {
        $client = static::createClient();
        $client->request('POST', '/turn/1/hit', [], [], [
            'HTTP_Authorization' => 'Bearer YOUR_VALID_JWT_TOKEN'
        ]);

        $this->assertResponseStatusCodeSame(400); // Hypothèse : la route doit refuser sans wager
    }
}