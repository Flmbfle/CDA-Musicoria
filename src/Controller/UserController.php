<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/profil/edition/{id}', name: 'profil.modifier')]
    /**
     * Cette fonction permet de modifier les informations du profil utilisateur
     *
     * @param User $choosenUser
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */

    public function edit(Utilisateur $user, Request $request, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute('security.login');
        }

        // dd($user, $this->getUser());
        if($this->getUser() !== $user)
        {
            return $this->redirectToRoute('accueil');
        }

        $form = $this->createForm(UtilisateurType::class, $user);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $user = $form->getData();
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre profil à été modifié avec succès !'
            );

            return $this->redirectToRoute('profil');
        } 

        return $this->render('pages/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
