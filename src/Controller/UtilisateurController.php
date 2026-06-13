<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Role; 
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/utilisateur')]
final class UtilisateurController extends AbstractController
{
    #[Route('/mon-profil', name: 'app_utilisateur_profil', methods: ['GET'])]
        public function monProfil(): Response
        {
            
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            
            return $this->render('utilisateur/show.html.twig', [
                'utilisateur' => $this->getUser(),
            ]);
        }

    #[IsGranted('ROLE_EMPLOYE')]    
    #[Route(name: 'app_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurRepository->findAll(),
        ]);
    }

    
    #[IsGranted('ROLE_EMPLOYE')]
    #[Route('/new', name: 'app_utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $roleId = $this->isGranted('ROLE_ADMIN') ? 2 : 3;
            $role = $entityManager->getRepository(Role::class)->find($roleId);
            $utilisateur->setRoleObjet($role);

            if ($this->isGranted('ROLE_ADMIN')) {
                $utilisateur->setIsVerified(true);
            }

            if ($utilisateur->getPassword()) {
                $hashedPassword = $passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword());
                $utilisateur->setPassword($hashedPassword);
            }

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateur_index');
        }

        return $this->render('utilisateur/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_EMPLOYE')]
    #[Route('/{id}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {

        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont bien été mises à jour.');

            if ($this->isGranted('ROLE_EMPLOYE') and $this->getUser() !== $utilisateur) {
                return $this->redirectToRoute('app_utilisateur_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_utilisateur_profil');
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }


    #[IsGranted('ROLE_EMPLOYE')]
    #[Route('/{id}/toggle', name: 'app_utilisateur_toggle', methods: ['POST'])]
    public function toggle(Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($utilisateur->isVerified()) {
            $utilisateur->setIsVerified(false);
            
            $nouveauEmail = $utilisateur->getEmail() . '_ARCHIVE_' . time();
            $utilisateur->setEmail($nouveauEmail);
        } 
        else {
            $utilisateur->setIsVerified(true);
            
            $emailActuel = $utilisateur->getEmail();
            if (str_contains($emailActuel, '_ARCHIVE_')) {
                $emailOriginal = explode('_ARCHIVE_', $emailActuel)[0];
                $utilisateur->setEmail($emailOriginal);
            }
        }
        
        $entityManager->flush();

        return $this->redirectToRoute('app_utilisateur_show', ['id' => $utilisateur->getId()]);
    }
}
