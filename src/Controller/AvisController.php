<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
final class AvisController extends AbstractController
{
    #[Route(name: 'app_avis_index', methods: ['GET'])]
    public function index(AvisRepository $avisRepository): Response
    {
        return $this->render('avis/index.html.twig', [
            'avis' => $avisRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_avis_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $avi = new Avis();

        $avi->setStatut('En attente');                     
        $avi->setDatePublication(new \DateTimeImmutable()); 
        $avi->setUtilisateur($this->getUser());            

        $idCommande = $request->query->get('commande');
        if ($idCommande) {
            $commande = $entityManager->getRepository(\App\Entity\Commande::class)->find($idCommande);
            if ($commande && $commande->getUtilisateur() === $this->getUser()) {
                $avi->setCommande($commande);
            }
        }

        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($avi);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été enregistré avec succès et sera publié après validation par notre équipe.');

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/new.html.twig', [
            'avi' => $avi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_avis_show', methods: ['GET'])]
    public function show(Avis $avi): Response
    {
        return $this->render('avis/show.html.twig', [
            'avi' => $avi,
        ]);
    }

    
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'app_avis_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_avis_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/edit.html.twig', [
            'avi' => $avi,
            'form' => $form,
        ]);
    }

    
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_avis_delete', methods: ['POST'])]
    public function delete(Request $request, Avis $avi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avi->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($avi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_avis_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/valider', name: 'app_avis_valider', methods: ['POST'])]
    public function valider(Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $avi->setStatut('Validé');
        $entityManager->flush();

        $this->addFlash('success', 'L\'avis a bien été validé et est maintenant affiché sur la page d\'accueil.');

        return $this->redirectToRoute('app_avis_index');
    }

    #[Route('/{id}/refuser', name: 'app_avis_refuser', methods: ['POST'])]
    public function refuser(Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($avi);
        $entityManager->flush();

        $this->addFlash('warning', 'L\'avis a été refusé et supprimé de la base de données.');

        return $this->redirectToRoute('app_avis_index');
    }

}
