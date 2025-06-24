<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
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

    public function testUserRegistration(): void
    {
        $client = static::createClient();
        
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $client->request('POST', '/user', [], [], [], json_encode($userData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testUserRegistrationDuplicateEmail(): void
    {
        $client = static::createClient();
        
        $userData = [
            'username' => 'testuser1',
            'email' => 'duplicate@example.com',
            'password' => 'password123'
        ];
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        
        $userData['username'] = 'testuser2';
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testJWTAuthentication(): void
    {
        $client = static::createClient();
        
        $credentials = [
            'username' => 'admin',
            'password' => 'admin'
        ];
        
        $client->request('POST', '/login_check', [], [], [], json_encode($credentials));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        
        $token = $data['token'];
        $client->request('GET', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUserRegistrationValidation(): void
    {
        $client = static::createClient();
        
        $userData = [
            'username' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'password123'
        ];
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $userData = [
            'username' => 'ab',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'ab'
        ];
        $client->request('POST', '/user', [], [], [], json_encode($userData));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetUserListAsAdmin(): void
    {
        $client = static::createClient();
        
        $token = $this->getAdminToken($client);
        
        $client->request('GET', '/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetUserListAsRegularUser(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUserListWithPagination(): void
    {
        $client = static::createClient();
        
        $token = $this->getAdminToken($client);
        
        $client->request('GET', '/user?limit=5&page=0', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetCurrentUserProfile(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function testGetUserByUuidAsAdmin(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        $userData = json_decode($client->getResponse()->getContent(), true);
        $userId = $userData['id'];
        
        $adminToken = $this->getAdminToken($client);
        
        $client->request('GET', '/user/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetUserByUuidAsRegularUser(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/user/some-uuid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateCurrentUserProfile(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $updateData = [
            'username' => 'updateduser',
            'email' => 'updated@example.com'
        ];
        
        $client->request('PATCH', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($updateData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('updateduser', $data['username']);
        $this->assertEquals('updated@example.com', $data['email']);
    }

    public function testUpdateUserByUuidAsAdmin(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client);
        
        $client->request('GET', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        $userData = json_decode($client->getResponse()->getContent(), true);
        $userId = $userData['id'];
        
        $adminToken = $this->getAdminToken($client);
        
        $updateData = [
            'username' => 'adminupdated',
            'email' => 'adminupdated@example.com'
        ];
        
        $client->request('PATCH', '/user/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ], json_encode($updateData));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testDeleteCurrentUser(): void
    {
        $client = static::createClient();
        
        $token = $this->createUserAndGetToken($client);
        
        $client->request('DELETE', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeleteUserByUuidAsAdmin(): void
    {
        $client = static::createClient();
        
        $userToken = $this->createUserAndGetToken($client, 'deleteuser', 'delete@example.com');
        
        $client->request('GET', '/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken
        ]);
        $userData = json_decode($client->getResponse()->getContent(), true);
        $userId = $userData['id'];
        
        $adminToken = $this->getAdminToken($client);
        
        $client->request('DELETE', '/user/' . $userId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testUnauthenticatedAccess(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/user/profile');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $client->request('GET', '/user');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $client->request('PATCH', '/user/profile');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        
        $client->request('DELETE', '/user/profile');
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