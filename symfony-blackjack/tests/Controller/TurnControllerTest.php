<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class TurnControllerTest extends ApiTestCase
{
        /**
     * @group create_turn
     */
    public function testCreateTurn(): void
    {
        // Se connecter en tant qu'utilisateur de test
        $this->logIn('test_user', 'test_password');

        // Créer un jeu pour le test
        $game = $this->createTestGame();

        // Requête POST pour créer un nouveau tour
        $this->client->request('POST', '/game/' . $game->getId() . '/turn');
        $response = $this->client->getResponse();

        // Vérifier la réponse
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertJsonResponse($response, [
            'id' => 1,
            'game' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
            ],
            'user' => [
                'id' => 1,
                'username' => 'test_user',
            ],
            'status' => 'pending',
            'cards' => [],
            'gains' => 0,
        ]);
    }
}