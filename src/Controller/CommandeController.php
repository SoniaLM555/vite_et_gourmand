<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;


#[Route('/commande')]
final class CommandeController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/creer/{id}', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Menu $menu, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, CommandeRepository $commandeRepository): Response
    {
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        
        if ($menu->getQuantiteRestante() <= 0) {
            $this->addFlash('danger', 'Désolé, ce menu est épuisé.');
            return $this->redirectToRoute('app_menu_index');
        }

        $commande = new Commande();
        $commande->addMenu($menu);
        $commande->setUtilisateur($user);

        
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
           
            $datePrestationSaisie = $commande->getDatePrestation();
            $commandesDuMemeJour = $commandeRepository->findBy(['datePrestation' => $datePrestationSaisie]);
            
            
            if (count($commandesDuMemeJour) >= 2) {
                $this->addFlash('danger', 'Julie et José ne sont plus disponibles pour une livraison le ' . $datePrestationSaisie . '. Veuillez choisir une autre date.');
                return $this->render('commande/new.html.twig', [
                    'commande' => $commande,
                    'menu' => $menu,
                    'form' => $form->createView(),
                ]);
            }

            
            $villeClient = strtolower(trim($user->getVille()));
            $prixLivraison = 0.0;

            if ($villeClient !== 'bordeaux') {
                $distanceSimulee = 15.0; 
                $prixLivraison = 5.0 + ($distanceSimulee * 0.59);
            }
            $commande->setPrixLivraison($prixLivraison);

    
            $nombreConvives = $commande->getNombrePersonne();
            $nbMinimalMenu = $menu->getNombrePersonneMin(); 

            
            if ($nombreConvives < $nbMinimalMenu) {
                $this->addFlash('danger', 'Vous devez commander ce menu pour au moins ' . $nbMinimalMenu . ' personnes.');
                return $this->render('commande/new.html.twig', [
                    'commande' => $commande,
                    'menu' => $menu,
                    'form' => $form->createView(),
                ]);
            }

            $prixTotalMenu = $menu->getPrixParPersonne() * $nombreConvives * $commande->getQuantite();

            
            if ($nombreConvives >= ($nbMinimalMenu + 5)) {
                $prixTotalMenu = $prixTotalMenu * 0.90; 
            }
            $commande->setPrixMenu($prixTotalMenu);

            
            $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
            $commande->setDateCommande((new \DateTime())->format('d/m/Y'));
            $commande->setStatut('En attente'); 
            $commande->setRestitutionMateriel(false);

            
            $menu->setQuantiteRestante($menu->getQuantiteRestante() - $commande->getQuantite());

            
            $entityManager->persist($commande);
            $entityManager->flush();

            
            $email = (new TemplatedEmail())
                ->from(new Address('admin@test.com', 'Vite & Gourmand'))
                ->to((string)$user->getEmail())
                ->subject('Confirmation de votre commande ' . $commande->getNumeroCommande())
                ->htmlTemplate('emails/confirmation_commande.html.twig')
                ->context([
                    'user' => $user,
                    'commande' => $commande,
                    'menu' => $menu
                ]);
            
            $mailer->send($email);

            $this->addFlash('success', 'Votre commande a bien été enregistrée ! Un mail de confirmation vous a été envoyé.');
            return $this->redirectToRoute('app_home'); 
        }

        return $this->render('commande/new.html.twig', [
            'commande' => $commande,
            'menu' => $menu,
            'form' => $form->createView(),
        ]);
    } 


    #[Route('/panier/{id}', name: 'app_commande_panier', methods: ['GET'])]
    public function panier(Menu $menu): Response
    { 
        $commande = new Commande();
        $commande->addMenu($menu);
        $commande->setPrixMenu($menu->getPrixParPersonne());
        $commande->setDatePrestation((new \DateTime())->format('d/m/Y'));

        return $this->render('commande/panier.html.twig', [
            'commande' => $commande,
            'menu' => $menu,
        ]);
    }
}
