<?php

namespace App\EntityListener;

use App\Entity\Utilisateur;
use Monolog\Handler\Curl\Util;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserListener
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function prePersist(Utilisateur $user)
    {
        $this->encodePassword($user);
    }

    public function preUpdate(Utilisateur $user)
    {
        $this->encodePassword($user);
    }

    /**
     * Encode password based on plain password
     *
     * @param Utilisateur $user
     * @return void
     */
    public function encodePassword(Utilisateur $user)
    {
        if ($user->getPlainPassword() === null) {
            return;
        }

        $user->setPassword(
            $this->hasher->hashPassword(
                $user,
                $user->getPlainPassword()
            )
        );

        $user->setPlainPassword('null');
    }
}
