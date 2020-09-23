<?php


namespace BlogBundle\Service\Users;


use BlogBundle\Entity\Role;
use BlogBundle\Entity\User;
use BlogBundle\Repository\UserRepository;
use BlogBundle\Service\Encryption\ArgonEncryption;
use BlogBundle\Service\Roles\RoleService;
use Symfony\Component\Security\Core\Security;
use BlogBundle\Service\Users\UserServiceInterface;

class UserService implements UserServiceInterface
{
    private $security;
    private $userRepository;
    private $encryptionService;
    private $roleService;
    public function __construct(Security $security,
                                UserRepository $userRepository,
                                ArgonEncryption  $encryptionService,
                                RoleService $roleService)
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->encryptionService = $encryptionService;
        $this->roleService = $roleService;
    }

    /**
     * @param string $email
     * @return User|null|object
     */
    public function findOneByEmail(string $email): ?User
    {
        return  $this->userRepository->findOneBy(['email'=>$email]);
    }

    public function save(User $user): bool
    {
        $passwordHash = $this->encryptionService->hash($user->getPassword());
        $user->setPassword($passwordHash);
        $userRole = $this->roleService->findOneBy("ROLE_USER");
        $user->addRole($userRole);

        return $this->userRepository->insert($user);
    }

    /**
     * @param int $id
     * @return User|null|object
     */
    public function findOneById(int $id): ?User
    {
        return  $this->userRepository->find($id);
    }

    /**
     * @param User $user
     * @return User|null|object
     */
    public function findOne(User $user): ?User
    {
        return $this->userRepository->find($user);
    }

    /**
     * @return User|null|object
     */
    public function currentUser(): ?User
    {
        return $this->security->getUser();
    }

    public function update(User $user): bool
    {
        return $this->userRepository->update($user);
    }
}