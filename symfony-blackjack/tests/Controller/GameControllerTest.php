<?php
// tests/Controller/GameControllerTest.php
// tests/Controller/GameControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Service\GameService;
use App\Entity\User;

class GameControllerTest extends WebTestCase
{
    private $client;
    private $gameServiceMock;

    protected function setUp(): void
    {
        // Créer le client
        $this->client = static::createClient();

        // Mock GameService
        $this->gameServiceMock = $this->createMock(GameService::class);

        // Créer un utilisateur réel avec le rôle admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']); // Assuming 'ROLE_ADMIN' is the role for admins
        $admin->setPassword('password');
        $admin->setUsername('admin');
        $admin->setWallet(1000);
        $admin->setCreationDate(new \DateTime());
        $admin->setLastUpdateDate(new \DateTime());

        // Simuler l'utilisateur connecté en tant qu'admin
        $this->client->loginUser($admin);

        // Remplacer le service dans le conteneur
        $container = static::getContainer();
        $container->set('App\Service\GameService', $this->gameServiceMock);
    }

    public function testGetListOfGames()
    {
        // Mock la méthode getPaginatedGameList
        $this->gameServiceMock->method('getPaginatedGameList')
            ->willReturn([['game1', 'game2'], null]);

        // Envoyer une requête GET à /game
        $this->client->request('GET', '/game');

        // Vérifier le statut de la réponse
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Vérifier le contenu de la réponse
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testCreateGame()
    {
        // Vérifier que l'utilisateur est bien un administrateur
        $user = $this->client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertContains('ROLE_ADMIN', $user->getRoles());

        // Mock la méthode createGame
        $this->gameServiceMock->method('createGame')
            ->willReturn([['id' => 1, 'name' => 'New Game'], null]);

        // Mock la méthode initializeGame
        $this->gameServiceMock->method('initializeGame')
            ->willReturn([['id' => 1, 'name' => 'New Game'], null]);

        // Envoyer une requête POST à /game
        $this->client->request('POST', '/game');

        // Vérifier le statut de la réponse
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Vérifier le contenu de la réponse
        $this->assertJson($this->client->getResponse()->getContent());
    }

}
