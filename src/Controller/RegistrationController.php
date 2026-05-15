<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RoleRepository $roleRepository, \Symfony\Component\Mailer\MailerInterface $mailer ): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $defaultRole = $roleRepository->findOneBy(['libelle' => 'Utilisateur']);

                if ($defaultRole) {
                    $user->setRoleObjet($defaultRole); 
                }
            
            $entityManager->persist($user);
            $entityManager->flush();

           

            $email = (new TemplatedEmail())
                ->from(new Address('admin@test.com', 'Vite et Gourmand'))
                ->to((string) $user->getEmail())
                ->subject('Bienvenue chez Vite & Gourmand !')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'user' => $user, // On passe l'utilisateur pour le "Bonjour {{ user.prenom }}"
                ]);

            $mailer->send($email);    

            // do anything else you need here, like send an email

            $this->addFlash('success', 'Votre compte a bien été créé ! Vous pouvez désormais vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    
}
