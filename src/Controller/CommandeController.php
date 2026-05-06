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

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commande);
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
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

    #[Route('/valider/{id}', name: 'app_commande_valider', methods: ['POST'])]
    public function valider(Menu $menu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        
        if (!$this->isCsrfTokenValid('valider'.$menu->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('app_menu_index');
        }

        
        if ($menu->getQuantiteRestante() <= 0) {
            $this->addFlash('danger', 'Désolé, ce menu est épuisé.');
            return $this->redirectToRoute('app_menu_index');
        }

        
        $dateInput = $request->request->get('datePrestation');
        // Si aucune date n'est envoyée, on prend la date du jour par défaut
        $datePrestation = $dateInput ? \DateTime::createFromFormat('Y-m-d', $dateInput)->format('d/m/Y') : (new \DateTime())->format('d/m/Y');

        
        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->addMenu($menu);
        $commande->setDateCommande((new \DateTime())->format('d/m/Y'));
        $commande->setDatePrestation($datePrestation);
        $commande->setQuantite(1);
        $commande->setNumeroCommande('CMD-' . uniqid());
        $commande->setPrixMenu($menu->getPrixParPersonne());
        $commande->setPrixLivraison(0.0);
        $commande->setNombrePersonne(1);
        $commande->setStatut('En attente');
        $commande->setPretMateriel(false);
        $commande->setRestitutionMateriel(false);

        
        $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);

        $entityManager->persist($commande);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commande a bien été validée !');
        return $this->redirectToRoute('app_commande_index');
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
