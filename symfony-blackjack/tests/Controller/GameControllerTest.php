<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GameControllerTest extends WebTestCase
{
    private function authenticateClient($client)
    {
        $client->request('POST', '/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'admin',
            'password' => 'admin'
        ]));

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
    }

    public function testCreateGame()
    {
        $client = static::createClient();
        $this->authenticateClient($client);

        try {
            $client->request('POST', '/game', [], [], ['CONTENT_TYPE' => 'application/json']);

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } catch (\Exception $e) {
            $this->fail('Exception occurred: ' . $e->getMessage());
        }
    }

    public function testGamePlay()
    {
        $client = static::createClient();
        $this->authenticateClient($client);

        try {
            $client->request('POST', '/game/1/turn', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
                'action' => 'hit'
            ]));

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } catch (\Exception $e) {
            $this->fail('Exception occurred: ' . $e->getMessage());
        }
    }
}
