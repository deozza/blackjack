<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Service\UserService;
use App\Entity\User;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $userServiceMock;

    protected function setUp(): void
    {
        // Créer le client
        $this->client = static::createClient();

        // Mock UserService
        $this->userServiceMock = $this->createMock(UserService::class);

        // Remplacer le service dans le conteneur
        $container = static::getContainer();
        $container->set('App\Service\UserService', $this->userServiceMock);

        // Simuler l'authentification en tant qu'admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']); // Assuming 'ROLE_ADMIN' is the role for admins
        // Set other admin properties as needed
        // ...

        $this->client->loginUser($admin);
    }

    public function testGetListOfUsers()
    {
        // Simuler le retour des utilisateurs
        $this->userServiceMock->method('getPaginatedUserList')
            ->willReturn([['user1', 'user2'], null]);

        // Envoyer une requête GET à /user
        $this->client->request('GET', '/user');

        // Vérifier le statut de la réponse
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Vérifier le contenu de la réponse
        $this->assertJson($this->client->getResponse()->getContent());
    }

}
