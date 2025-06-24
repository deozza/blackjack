<?php


namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Form\User\CreateUserType;
use App\Form\User\UpdateUserType;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private $userRepository;
    private $formFactory;
    private $passwordHasherFactory;
    private $em;
    private $form;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->formFactory,
            $this->passwordHasherFactory,
            $this->em
        );
    }

    public function testGetPaginatedUserList(): void
    {
        $users = [new User(), new User()];
        
        $this->userRepository->expects($this->once())
            ->method('findBy')
            ->with([], [], 10, 0)
            ->willReturn($users);
        
        [$result, $error] = $this->userService->getPaginatedUserList(10, 0);
        
        $this->assertSame($users, $result);
        $this->assertNull($error);
    }

    public function testCreateUserSuccess(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'password123'
        ];

        $user = new User();
        
        // Configuration des mocks
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreateUserType::class)
            ->willReturn($this->form);
            
        $this->form->expects($this->once())
            ->method('submit')
            ->with($userData);
            
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($user);
        
        $this->userRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['email' => 'test@example.com']],
                [['username' => 'testuser']]
            )
            ->willReturnOnConsecutiveCalls(null, null);
            
        // Utiliser un objet hasher correctement typé
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $this->passwordHasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with($this->isInstanceOf(User::class))
            ->willReturn($hasher);
            
        $hasher->expects($this->once())
            ->method('hash')
            ->with('password123')
            ->willReturn('hashed_password');
        
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function($savedUser) {
                return $savedUser->getEmail() === 'test@example.com' &&
                       $savedUser->getUsername() === 'testuser' &&
                       $savedUser->getPassword() === 'hashed_password' &&
                       $savedUser->getWallet() === 1000;
            }), true);
        
        [$result, $error] = $this->userService->createUser($userData);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertNull($error);
    }

    public function testCreateUserWithExistingEmail(): void
    {
        $userData = [
            'email' => 'existing@example.com',
            'username' => 'newuser',
            'password' => 'password123'
        ];

        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');
        
        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->form);
            
        $this->form->expects($this->once())
            ->method('submit')
            ->with($userData);
            
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        // Simuler qu'un utilisateur avec cet email existe déjà
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);
        
        [$result, $error] = $this->userService->createUser($userData);
        
        $this->assertNull($result);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(400, $error->getCode());
        $this->assertStringContainsString('email', $error->getMessage());
    }

    public function testGetUserById(): void
    {
        $userId = '123';
        $user = new User();
        
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $userId])
            ->willReturn($user);
        
        [$result, $error] = $this->userService->getUser($userId);
        
        $this->assertSame($user, $result);
        $this->assertNull($error);
    }

    public function testGetUserNotFound(): void
    {
        $userId = 'non-existent';
        
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $userId])
            ->willReturn(null);
        
        [$result, $error] = $this->userService->getUser($userId);
        
        $this->assertNull($result);
        $this->assertInstanceOf(\Error::class, $error);
        $this->assertEquals(404, $error->getCode());
        $this->assertEquals('User not found', $error->getMessage());
    }

    public function testDeleteUser(): void
    {
        $userId = '123';
        $user = new User();
        
        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $userId])
            ->willReturn($user);
        
        $this->em->expects($this->once())
            ->method('remove')
            ->with($user);
        
        $this->em->expects($this->once())
            ->method('flush');
        
        [$result, $error] = $this->userService->deleteUser($userId);
        
        $this->assertNull($result);
        $this->assertNull($error);
    }
}