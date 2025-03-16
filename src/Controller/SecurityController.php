<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\JWTService;
use App\Form\RegistrationType;
use App\Form\ResetPasswordType;
use App\Service\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use App\Form\ResetPasswordRequestFormType;
use App\Security\Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{

    /**
     * Cette fonction nous permet de se connecter
     *
     * @return void
     */
    #[Route('/connexion', name: 'security.login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('pages/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }


    /**
     * Cette fonction nous permet de se déconnecter
     *
     * @return void
     */
    #[Route(path: '/deconnexion', name: 'security.logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Cette fonction nous permet de s'inscrire 
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/inscription', 'security.registration', methods: ['GET', 'POST'])]
    public function registration(
        Request $request, 
        EntityManagerInterface $manager, 
        UserPasswordHasherInterface $passwordHasher, 
        UserAuthenticatorInterface $userAuthenticator, 
        Authenticator $authenticator,
        SendEmailService $mail, 
        JWTService $jwt): Response
    {
        $user = new Utilisateur();
        $user->setRoles(['ROLE_USER']);

        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            // Persist user in the database
            $manager->persist($user);
            $manager->flush();

            $this->addFlash('success', 'Votre compte a bien été créé. Un email de confirmation vous a été envoyé.');

            // On génére le JWTToken de l'utilisateur
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];
            $payload = [
                'user_id' => $user->getId(),
            ];

            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

            // Envoi de l'email de confirmation
            $mail->send(
                'no-reply@musicoria.fr',
                $user->getEmail(),
                'Activation de votre compte Musicoria',
                'email_confirmation',
                compact('user', 'token')
            );

            // return $this->redirectToRoute('security.login');
            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
        }

        return $this->render('pages/security/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UtilisateurRepository $usersRepository, EntityManagerInterface $em): Response
    {
        //On vérifie si le token est valide, n'a pas expiré et n'a pas été modifié
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            // On récupère le payload
            $payload = $jwt->getPayload($token);

            // On récupère le user du token
            $user = $usersRepository->find($payload['user_id']);

            //On vérifie que l'utilisateur existe et n'a pas encore activé son compte
            if($user && !$user->getIsVerified()){
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success', 'Utilisateur activé');
                return $this->redirectToRoute('profile_index');
            }
        }
        // Ici un problème se pose dans le token
        $this->addFlash('danger', 'Le token est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwt, SendEmailService $mail, UtilisateurRepository $usersRepository): Response
    {
        $user = $this->getUser();
            if (!$user instanceof Utilisateur) {
                // Si $user n'est pas une instance de la classe Utilisateur (par exemple, non authentifié)
                $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page');
                return $this->redirectToRoute('app_login');
            }


        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');    
        }

        if($user->isVerified()){
            $this->addFlash('warning', 'Cet utilisateur est déjà activé');
            return $this->redirectToRoute('profile_index');    
        }

        // On génère le JWT de l'utilisateur
        // On crée le Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // On crée le Payload
        $payload = [
            'user_id' => $user->getId()
        ];

        // On génère le token
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        // On envoie un mail
        $mail->send(
            'no-reply@monsite.net',
            $user->getEmail(),
            'Activation de votre compte sur le site e-commerce',
            'email_confirmation',
            compact('user', 'token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('accueil');
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
                return $this->redirectToRoute('security.login');

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
                $form = $this->createForm(ResetPasswordType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid())
                {
                    $user->setPassword(
                        $passwordhasher->hashPassword($user, $form->get('password')->getData())
                    );

                    $em->flush();

                    $this->addFlash('success','Mot de passe réinitialisé avec succès');
                    return $this->redirectToRoute('security.login');
                }
                return $this->render('security/reset_password.html.twig',
                [
                    'passForm' => $form->createView(),
                ]);
            }
        }
        $this->addFlash('danger','Token invalide ou expiré');
        return $this->redirectToRoute('security.login');
    }
}