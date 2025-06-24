<?php
namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testPostUser(): void
    {
        $this->client->request(
            'POST',
            '/user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'newuser',
                'email' => 'newuser@example.com',
                'password' => 'password123'
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('username', $responseContent);
        $this->assertEquals('newuser', $responseContent['username']);
        $this->assertArrayHasKey('email', $responseContent);
        $this->assertEquals('newuser@example.com', $responseContent['email']);
        
        // Vérifier que l'utilisateur existe en base de données
        $user = $this->userRepository->findOneBy(['username' => 'newuser']);
        $this->assertNotNull($user);
    }
    
    public function testPostUserWithInvalidData(): void
    {
        // Test avec un email invalide
        $this->client->request(
            'POST',
            '/user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'invaliduser',
                'email' => 'invalid-email',
                'password' => 'password123'
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        
        // Test avec un username trop court
        $this->client->request(
            'POST',
            '/user',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'a',
                'email' => 'valid@example.com',
                'password' => 'password123'
            ])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }
    
    public function testGetCurrentUserInfos(): void
    {
        // Créer un utilisateur de test
        $testUser = new User();
        $testUser->setUsername('testuser');
        $testUser->setEmail('testuser@example.com');
        $testUser->setPassword('hashed_password');
        $testUser->setWallet(1000);
        $testUser->setCreationDate(new \DateTime());
        $testUser->setLastUpdateDate(new \DateTime());
        
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();
        
        // Authentifier l'utilisateur
        $this->client->request(
            'POST',
            '/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'testuser',
                'password' => 'password123'
            ])
        );
        
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];
        
        // Accéder au profil avec le token
        $this->client->request(
            'GET',
            '/user/profile',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('testuser', $responseContent['username']);
        $this->assertEquals('testuser@example.com', $responseContent['email']);
        $this->assertEquals(1000, $responseContent['wallet']);
    }
}