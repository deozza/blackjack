<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserServiceTest extends TestCase
{
    private $userService;

    protected function setUp(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);

        $this->userService = new UserService($userRepository, $entityManager, $formFactory, $passwordHasher, $logger, $validator);
    }

    public function testCreateUser()
    {
        $data = [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'password123'
        ];

        $user = $this->userService->createUser($data);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('testuser@example.com', $user->getEmail());
    }
}
