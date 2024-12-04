<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Service\JWTService;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use App\Repository\UtilisateurRepository;
use App\Service\SendEmailService;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    // #[Route('/register', name: 'app_register')]
    // public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, JWTService $jwt, SendEmailService $email): Response
    // {
    //     $user = new Utilisateur();
    //     $form = $this->createForm(RegistrationFormType::class, $user);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         /** @var string $plainPassword */
    //         $plainPassword = $form->get('plainPassword')->getData();

    //         // encode the plain password
    //         $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

    //         // // Récupérer le rôle "CLIENT" depuis la base de données
    //         $role = $entityManager->getRepository(Role::class)->findOneBy(['nom' => 'ROLE_CLIENT']);
    //             if ($role) {
    //                 // Assigner le rôle à l'utilisateur
    //                 $user->setRoles(['ROLE_CLIENT']);
    //             } else {
    //                 // Optionnel : Gérer le cas où le rôle n'existe pas
    //                 throw new \Exception('Le rôle CLIENT n\'existe pas dans la base de données.');
    //             }
    //         // dd($user);
    //         $entityManager->persist($user);
    //         $entityManager->flush();

    //         // Generer un Token
    //         // Header
    //         $header = [
    //             'typ'=> 'JWT',
    //             'alg'=> 'HS256'
    //         ];

    //         // Payload
    //         $payload = [
    //             'user_id' => $user->getId(),
    //         ];
    //         // On encode en base64
    //         // $base64Header = base64_encode(json_encode($header));

    //         // On génère le Token
    //         $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

    //         // Envoyer le mail

    //         $email->send(
    //             'no-reply@musicoria.fr',
    //             $user->getEmail(),
    //             'Activation de votre compte Musicoria',
    //             'register',
    //             compact('user', 'token'),
    //         );


    //         $this->addFlash('success','Utilisateur inscrit, veuillez cliquer sur le lien reçu pour confirmer votre adresse email.');

    //         return $this->redirectToRoute('app_main');
    //     }

    //     return $this->render('registration/register.html.twig', [
    //         'registrationForm' => $form,
    //     ]);
    // }

    #[Route('/verify/{{token}}', name: 'app_verify_user')]
    public function verifyUserEmail($token, JWTService $jwt, UtilisateurRepository $utilisateurRepository , EntityManagerInterface $em): Response
    {
        //On vérifie si le token est OK
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
            //Le token est valide !
            //Récupération des données
            $payload = $jwt->getPayload($token);
            
            // On récupère l'utilisateur
            $user = $utilisateurRepository->find($payload['user_id']);

            // Vérif que l'on a bien un user et qu'il n'est pas deja activé
            if($user && !$user->isVerified()) {
                $user->setVerified(true);
                $em->flush();

                $this->addFlash('success','Utilisateur activé');
                return $this->redirectToRoute('app_main');
            }
        }
        $this->addFlash('danger','Token invalide ou expiré');
        return $this->redirectToRoute('security.login');
    }
}