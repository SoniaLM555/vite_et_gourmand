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
    #[IsGranted('ROLE_ADMIN')]
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
        $avi->setDatePublication(new \DateTimeImmutable());  
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $avi->setStatut('Validé');
        } else {
            $avi->setStatut('En attente');
        }

        $idCommande = $request->query->get('commande');
        if ($idCommande) {
            $commande = $entityManager->getRepository(\App\Entity\Commande::class)->find($idCommande);
            
            if ($commande && $commande->getUtilisateur() === $this->getUser()) {
                
                $avisBloquant = $entityManager->getRepository(Avis::class)->findOneBy([
                    'commande' => $commande,
                    'statut' => ['En attente', 'Validé'] 
                ]);
                
                if ($avisBloquant) {
                    $this->addFlash('danger', 'Un avis actif est déjà enregistré pour cette commande (Statut : ' . $avisBloquant->getStatut() . ').');
                    return $this->redirectToRoute('app_home'); 
                }

                $avi->setCommande($commande);
                $avi->setUtilisateur($commande->getUtilisateur());
            }
        }

        $form = $this->createForm(AvisType::class, $avi, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $numeroSaisi = $form->has('numeroCommandeSaisi') ? $form->get('numeroCommandeSaisi')->getData() : null;

            if ($numeroSaisi) {
                $commande = $entityManager->getRepository(\App\Entity\Commande::class)->findOneBy([
                    'numeroCommande' => $numeroSaisi
                ]);

                if (!$commande) {
                    $this->addFlash('danger', 'Le numéro de commande saisi n\'existe pas.');
                    return $this->render('avis/new.html.twig', [
                        'avi' => $avi,
                        'form' => $form,
                    ]);
                }

                $avisBloquantAdmin = $entityManager->getRepository(Avis::class)->findOneBy([
                    'commande' => $commande,
                    'statut' => ['En attente', 'Validé'] 
                ]);

                if ($avisBloquantAdmin) {
                    $this->addFlash('danger', 'Un avis actif existe déjà pour la commande ' . $numeroSaisi);
                    return $this->render('avis/new.html.twig', [
                        'avi' => $avi,
                        'form' => $form,
                    ]);
                }

                $avi->setCommande($commande);
                $avi->setUtilisateur($commande->getUtilisateur());
            }

            if (!$avi->getCommande()) {
                $this->addFlash('danger', 'Erreur : Impossible d\'enregistrer un avis sans numéro de commande valide.');
                return $this->render('avis/new.html.twig', [
                    'avi' => $avi,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($avi);
            $entityManager->flush();

            if ($this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('success', 'L\'avis a été créé et publié automatiquement sur le site.');
            } else {
                $this->addFlash('success', 'Votre avis a été enregistré avec succès et sera publié après validation par notre équipe.');
            }

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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/valider', name: 'app_avis_valider', methods: ['POST'])]
    public function valider(Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $avi->setStatut('Validé');
        $entityManager->flush();

        $this->addFlash('success', 'L\'avis a bien été validé et est maintenant affiché sur la page d\'accueil.');

        return $this->redirectToRoute('app_avis_index');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/refuser', name: 'app_avis_refuser', methods: ['POST'])]
    public function refuser(Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $avi->setStatut('Refusé');
        $entityManager->flush();

        $this->addFlash('warning', 'L\'avis a été refusé et son statut a été mis à jour.');

        return $this->redirectToRoute('app_avis_index');
    }
}