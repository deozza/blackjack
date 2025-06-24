<?php

namespace App\Service;

use App\Entity\User;
use App\Form\User\CreateUserType;
use App\Form\User\UpdateUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserService
{

    private UserRepository $userRepository;
    private FormFactoryInterface $formFactory;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, FormFactoryInterface $formFactory, PasswordHasherFactoryInterface $passwordHasherFactory, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->formFactory = $formFactory;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->em = $em;
    }

    public function getPaginatedUserList(int $limit, int $page): array
    {
        $users = $this->userRepository->findBy([], [], $limit, $page * $limit);
        return [$users, null];        
    }

public function createUser(array $userData): array
{
    try {
        // Cette partie est correcte
        $form = $this->formFactory->create(CreateUserType::class);
        $form->submit($userData);
        
        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return [null, new \Error(json_encode($errors), 400)];
        }
        
        // Cette partie est correcte - vérification de l'email existant
        $existingUser = $this->userRepository->findOneBy(['email' => $userData['email']]);
        
        if ($existingUser !== null) {
            return [null, new \Error('User with this email already exists', 400)];
        }
        
        // Obtenez l'utilisateur du formulaire
        $user = $form->getData();
        
        // IMPORTANT: Utiliser l'instance de User pour obtenir le hasher, comme attendu par le test
        $plainPassword = $userData['password'];
        $hashedPassword = $this->passwordHasherFactory->getPasswordHasher($user)->hash($plainPassword);
        $user->setPassword($hashedPassword);
        
        // Initialiser le portefeuille
        $user->setWallet(1000);
        $user->setCreationDate(new \DateTime());
        $user->setLastUpdateDate(new \DateTime());
        
        // Sauvegarder l'utilisateur
        $this->userRepository->save($user, true);
        
        return [$user, null];
    } catch (\Exception $e) {
        return [null, new \Error('Error creating user: ' . $e->getMessage(), 500)];
    }
}

    public function checkPayloadIsValidForCreateUser(array $data): array
    {
        $user = new User();
        $form = $this->formFactory->create(CreateUserType::class, $user);
        $form->submit($data);
        $errors = [];

        if(!$form->isValid()) {
            $errors = FormService::getFormErrors($form);
            return [null, $errors];
        }

        return [$user, null];
    }

    public function checkUserAlreadyExist(User $user): array
    {
        $errors = [];

        $existingEmail = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
        if(!empty($existingEmail) && $existingEmail->getId() !== $user->getId()) {
            $errors['email'][] = 'Email already exist';
        }

        $existingUsername = $this->userRepository->findOneBy(['username' => $user->getUsername()]);
        if(!empty($existingUsername) && $existingUsername->getId() !== $user->getId()) {
            $errors['username'][] = 'Username already exist';
        }

        return [$user, $errors];
    }

    public function createUserAcccordingToPayload(array $data): User
    {
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $hashedPassword = $this->passwordHasherFactory->getPasswordHasher(User::class)->hash('$data["password"]');

        $user->setPassword($hashedPassword);
        $user->setCreationDate(new \DateTime());
        $user->setLastUpdateDate(new \DateTime());
        $user->setWallet(1000);
        
        return $user;
    }

    public function getUser(string $id): array
    {
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if($user === null) {
            $err = new \Error('User not found', 404);
            return [null, $err];
        }

        return [$user, null];
    }

    public function updateUser(string $id, array $data): array
    {
        list($user, $err) = $this->getUser($id);
        if($err !== null) {
            return [null, $err];
        }

        list($user, $errors) = $this->checkPayloadIsValidForUpdateUser($user, $data);
        if(!empty($errors)) {
            $err = new \Error(json_encode($errors), 400);
            return [null, $err];
        }

        list($user, $errors) = $this->checkUserAlreadyExist($user);
        if(!empty($errors)) {
            $err = new \Error(json_encode($errors), 400);
            return [null, $err];
        }

        $this->userRepository->save($user);
        return [$user, null];
    }

    public function checkPayloadIsValidForUpdateUser(User $user, array $data): array
    {
        $form = $this->formFactory->create(UpdateUserType::class, $user);
        $form->submit($data, false);
        $errors = [];

        if(!$form->isValid()) {
            $errors = FormService::getFormErrors($form);
            return [null, $errors];
        }

        return [$user, null];
    }

    public function deleteUser(string $id): array
    {
        list($user, $err) = $this->getUser($id);
        if($user === null) {
            return [null, $err];
        }

        $this->em->remove($user);
        $this->em->flush();
        return [null, null];
    }
}