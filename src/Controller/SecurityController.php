<?php

namespace App\Controller;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UtilisateurRepository;
use App\Service\JWTService;
use App\Service\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path:'/mot-de-passe-oublie', name:'app_forgotten_password')]
    public function forgottenPassword(Request $request, UtilisateurRepository $utilisateurRepository, JWTService $jwt, SendEmailService $email): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Le formulaire est envoyé ET valide
            // On peux alors allez chercher l'utilisateur dans la base
            $user =  $utilisateurRepository->findOneByEmail($form->get('email')->getData());
            if ($user){
                // On a un utilisateur

                // Generer un Token
                // Header
                $header = [
                    'typ'=> 'JWT',
                    'alg'=> 'HS256'
                ];

                // Payload
                $payload = [
                    'user_id' => $user->getId(),
                ];

                // On génère le token
                $token = $jwt->generate($header, $payload,$this->getParameter('app.jwtsecret'));

                // Génération de l'URL vers app_reset_password  
                $url = $this->generateUrl('app_reset_password', ['token'=> $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Envoyer le mail

                $email->send(
                    'no-reply@musicoria.fr',
                    $user->getEmail(),
                    'Récupération de mot de passe Musicoria',
                    'password_reset',
                    compact('user', 'url'),
                );

                $this->addFlash('success','Email envoyé avec succès');
                return $this->redirectToRoute('app_login');

            }
            // Utilisateur non récupere
            $this->addFlash('danger','Un problème est survenu');
            return $this->redirectToRoute('app_reset_password');
        }

        return $this->render('security/reset_password_request.html.twig',
        [
            'requestPassForm' => $form->createView(),
        ]);
    }

    #[Route(path:'/mot-de-passe-oublie/{token}', name:'app_reset_password')]
    public function resetPassword($token, Request $request, UtilisateurRepository $utilisateurRepository, JWTService $jwt, UserPasswordHasherInterface $passwordhasher, EntityManagerInterface $em): Response
    {
        //On vérifie si le token est OK
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
            //Le token est valide !
            //Récupération des données
            $payload = $jwt->getPayload($token);
            
            // On récupère l'utilisateur
            $user = $utilisateurRepository->find($payload['user_id']);

            if($user) 
            {
                $form = $this->createForm(ResetPasswordFormType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid())
                {
                    $user->setPassword(
                        $passwordhasher->hashPassword($user, $form->get('password')->getData())
                    );

                    $em->flush();

                    $this->addFlash('success','Mot de passe réinitialisé avec succès');
                    return $this->redirectToRoute('app_login');
                }
                return $this->render('security/reset_password.html.twig',
                [
                    'passForm' => $form->createView(),
                ]);
            }
        }
        $this->addFlash('danger','Token invalide ou expiré');
        return $this->redirectToRoute('app_login');
    }
}