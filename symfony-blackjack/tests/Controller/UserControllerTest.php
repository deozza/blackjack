<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    public function testRegister()
    {
        $client = static::createClient();

        try {
            $client->request('POST', '/user', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
                'username' => 'testuser',
                'email' => 'testuser@example.com',
                'password' => 'TestPassword123'
            ]));

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } catch (\Exception $e) {
            $this->fail('Exception occurred: ' . $e->getMessage());
        }
    }
}
