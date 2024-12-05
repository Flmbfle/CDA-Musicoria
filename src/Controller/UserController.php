<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ResetPasswordType;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/profil', name: 'profil')]
    public function profil(): Response
    {
        return $this->render('pages/user/profil.html.twig', [
            'controller_name' => 'ProfilController',
        ]);
    }

    /**
     * Cette fonction permet de modifier les informations du profil utilisateur
     *
     * @param Utilisateur $choosenUser
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/profil/edition/{id}', name: 'profil.modifier', methods: ['GET','POST'])]
    public function edit(Utilisateur $user, Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $hasher): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute('security.login');
        }

        if($this->getUser() !== $user)
        {
            return $this->redirectToRoute('accueil');
        }

        $form = $this->createForm(UtilisateurType::class, $user);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            if($hasher->isPasswordValid($user, $form->getData()->getPlainPassword()))
            {
                $user = $form->getData();
                $manager->persist($user);
                $manager->flush();
    
                $this->addFlash(
                    'success',
                    'Votre profil à été modifié avec succès !'
                );
    
                return $this->redirectToRoute('profil');
            } else  
            {
                $this->addFlash(
                    'warning',
                    'Le mot de passe renseigné est incorrect !'
                );
            }

        }

        return $this->render('pages/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Cette fonction nous permet de modifier le mot de passe de l'utilisateur
     * 
     * @param Utilisateur $user
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route('/profil/reinitialisation-mot-de-passe/{id}', 'profil.modifier.password')]
    public function resetPassword(Utilisateur $user, Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute('security.login');
        }

        if($this->getUser() !== $user)
        {
            return $this->redirectToRoute('accueil');
        }

        $form = $this->createForm(ResetPasswordType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            if ($hasher->isPasswordValid($user, $form->getData()['plainPassword'])) {
                $user->setUpdatedAt(new \DateTimeImmutable());
                $user->setPlainPassword(
                    $form->getData()['newPassword']
                );
            
                $this->addFlash(
                    'success',
                    'Votre mot de passe a été modifié avec succès !'
                );

                $manager->persist($user);
                $manager->flush();


                return $this->redirectToRoute('profil');
            } else  
            {
                $this->addFlash(
                    'warning',
                    'Le mot de passe renseigné est incorrect !'
                );
            }
        }
        return $this->render('pages/user/reinitialisation.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
