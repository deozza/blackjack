<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractControllerTest
{
    public function testCreateUserWithValidData(): void
    {
        $userData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password123'
        ];

        $this->makeUnauthenticatedRequest('POST', '/user', $userData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($userData['username'], $data['username']);
        $this->assertEquals($userData['email'], $data['email']);
    }

    public function testCreateUserWithInvalidData(): void
    {
        $userData = [
            'username' => 'ab',
            'email' => 'invalid-email',
            'password' => '12345'
        ];

        $this->makeUnauthenticatedRequest('POST', '/user', $userData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('password', $data);
    }

    public function testCreateUserWithDuplicateUsername(): void
    {
        $userData = [
            'username' => 'admin',
            'email' => 'newemail@example.com',
            'password' => 'password123'
        ];

        $this->makeUnauthenticatedRequest('POST', '/user', $userData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertArrayHasKey('username', $data);
        $this->assertContains('Username already exist', $data['username']);
    }

    public function testGetCurrentUserProfile(): void
    {
        $this->makeAuthenticatedRequest('GET', '/user/profile');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function testUpdateCurrentUserProfile(): void
    {
        $updateData = [
            'email' => 'newemail_' . uniqid() . '@example.com'
        ];

        $this->makeAuthenticatedRequest('PATCH', '/user/profile', $updateData);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertEquals($updateData['email'], $data['email']);
    }

    public function testGetUserListAsAdmin(): void
    {
        $this->makeAuthenticatedRequest('GET', '/user');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertIsArray($data);
    }

    public function testGetUserListWithPagination(): void
    {
        $this->makeAuthenticatedRequest('GET', '/user?limit=5&page=0');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(5, count($data));
    }

    public function testGetUserListWithoutAuth(): void
    {
        $this->makeUnauthenticatedRequest('GET', '/user');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
} 