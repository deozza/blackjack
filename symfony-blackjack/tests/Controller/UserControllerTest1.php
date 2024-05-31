<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp(): void
    {
        // Création des utilisateurs
        $this->client = static::createClient();
        for ($i = 0; $i < 20; $i++) {
            $username = uniqid();
            $password = uniqid();

            $body = json_encode([
                'email' => $username . '@test.fr',
                'username' => $username,
                'password' => $password
                
            ]);

            $this->client->request('POST', '/user', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        }
    }

    public function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    public function test__createUser(): void
    {
        $this->createUser();
    }

    public function test__loginUser(): void
    {
        $this->loginUser('admin', 'admin');
    }

    public function test__getUserList(): void
    {
        $this->loginAndGetUserList('admin', 'admin');
    }

    private function createUser(): void
    {
        $username = uniqid();
        $password = uniqid();

        $body = json_encode([
            'email' => $username . '@test.fr',
            'password' => $password,
            'username' => $username
        ]);

        $this->client->request('POST', '/user', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
    }

    private function loginUser(string $username, string $password): void
    {
        $this->client->request('POST', '/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $username,
            'password' => $password,
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    private function loginAndGetUserList(string $username, string $password): void
    {
        $this->loginUser($username, $password);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $jwt = $data['token'];

        $this->client->request('GET', '/user', ['limit' => 10, 'page' => 1], [], ['HTTP_Authorization' => 'Bearer ' . $jwt]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(10, $data);
    }
}
