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

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouterAuPanier(Menu $menu, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $nbPersonnesPourCeMenu = (int) $request->request->get('quantite_personnes', 0);
        $minRequired = $menu->getNombrePersonneMin();

        if ($nbPersonnesPourCeMenu < $minRequired) {
            $this->addFlash('danger', 'Pour le menu "' . $menu->getTitre() . '", vous devez commander pour au moins ' . $minRequired . ' personnes.');
            return $this->redirectToRoute('app_menu_show', ['id' => $menu->getId()]);
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        $panier[$menu->getId()] = $nbPersonnesPourCeMenu;
        $session->set('panier', $panier);

        $this->addFlash('success', 'Le menu "' . $menu->getTitre() . '" a bien été ajouté à votre panier !');
        return $this->redirectToRoute('app_commande_panier');
    }

    #[Route('/panier/modifier-quantite/{id}', name: 'app_panier_modifier_quantite', methods: ['POST'])]
    public function modifierQuantitePanier(Menu $menu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $action = $request->request->get('action');

        if (isset($panier[$menu->getId()])) {
            if ($action === 'increment') {
                $panier[$menu->getId()]++;
            } elseif ($action === 'decrement') {
                $panier[$menu->getId()]--;
    
                if ($panier[$menu->getId()] < $menu->getNombrePersonneMin()) {
                    $this->addFlash('warning', 'Le seuil minimal pour le menu "' . $menu->getTitre() . '" est de ' . $menu->getNombrePersonneMin() . ' personnes.');
                    $panier[$menu->getId()] = $menu->getNombrePersonneMin();
                }
            }
        }

        $session->set('panier', $panier);
        return $this->redirectToRoute('app_commande_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer', methods: ['GET'])]
    public function supprimerDuPanier(Menu $menu, Request $request): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier', []);

        if (isset($panier[$menu->getId()])) {
            unset($panier[$menu->getId()]);
            $this->addFlash('success', 'Le menu "' . $menu->getTitre() . '" a été retiré de votre panier.');
        }

        $session->set('panier', $panier);
        return $this->redirectToRoute('app_commande_panier');
    }


    #[Route('/panier', name: 'app_commande_panier', methods: ['GET'])]
    public function afficherPanier(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        $session = $request->getSession();
        $panierSession = $session->get('panier', []);
        
        $elementsPanier = [];
        $totalMenusGlobal = 0.0;

        foreach ($panierSession as $menuId => $nbPersonnes) {
            $menu = $entityManager->getRepository(Menu::class)->find($menuId);
            
            if ($menu) {
                $prixBrutMenu = $menu->getPrixParPersonne() * $nbPersonnes;
                
                $aDroitAuDixPourcent = $nbPersonnes >= ($menu->getNombrePersonneMin() + 5);
                $montantRemise = 0.0;
                
                if ($aDroitAuDixPourcent) {
                    $montantRemise = $prixBrutMenu * 0.10;
                }

                $prixFinalPourCeMenu = $prixBrutMenu - $montantRemise;
                $totalMenusGlobal += $prixFinalPourCeMenu;

                $elementsPanier[] = [
                    'menu' => $menu,
                    'nbPersonnes' => $nbPersonnes,
                    'prixBrut' => $prixBrutMenu,
                    'remise' => $montantRemise,
                    'prixFinal' => $prixFinalPourCeMenu,
                    'aRemise' => $aDroitAuDixPourcent
                ];
            }
        }

        $villeClient = strtolower(trim($user->getVille()));
        $prixLivraison = 0.0;

        if ($villeClient !== 'bordeaux' && !empty($elementsPanier)) {
            $prixLivraison = 5.0 + (15.0 * 0.59);
        }

        $totalGeneralCommande = $totalMenusGlobal + $prixLivraison;

        return $this->render('commande/panier.html.twig', [
            'elements' => $elementsPanier,
            'prixLivraison' => $prixLivraison,
            'totalMenus' => $totalMenusGlobal,
            'totalGeneral' => $totalGeneralCommande,
            'ville' => $user->getVille()
        ]);
    }

    
    #[Route('/panier/estimer-livraison', name: 'app_panier_estimer_livraison', methods: ['POST'])]
    public function estimerLivraison(Request $request): Response
    {
        $session = $request->getSession();

        $adresse = trim($request->request->get('adressePrestation', ''));
        $ville = trim($request->request->get('villePrestation', ''));
        $cp = trim($request->request->get('codePostalPrestation', ''));
        
        $session->set('form_adresse', $adresse);
        $session->set('form_ville', $ville);
        $session->set('form_cp', $cp);
        $session->set('form_date', $request->request->get('datePrestation', ''));
        $session->set('form_heure', $request->request->get('heureLivraison', ''));

        if (!$adresse || !$ville || !$cp) {
            $this->addFlash('danger', 'Veuillez remplir l\'adresse, le code postal et la ville pour calculer les frais.');
            return $this->redirectToRoute('app_commande_panier');
        }

        $prixLivraison = 0.0;

        if (strtolower(trim($ville)) === 'bordeaux') {
            $session->set('livraison_calculee', 0.0);
            $this->addFlash('success', 'Livraison à Bordeaux : Gratuite ! Vous pouvez confirmer votre commande.');
            return $this->redirectToRoute('app_commande_panier');
        }

        $qgLat = 44.837789;
        $qgLon = -0.57918;

        // ÉTAPE A : Demander au traducteur d'adresse de l'État (BAN) les coordonnées GPS du lieu
        $adresseFormatee = urlencode($adresse . ' ' . $cp . ' ' . $ville);
        $urlApiAdresse = "https://api-adresse.data.gouv.fr/search/?q=" . $adresseFormatee . "&limit=1";

        try {
            $responseAdresse = file_get_contents($urlApiAdresse);
            $dataAdresse = json_decode($responseAdresse, true);

            if (empty($dataAdresse['features'])) {
                $session->remove('livraison_calculee');
                $this->addFlash('danger', 'Adresse introuvable. Julie & José livrent uniquement en France métropolitaine.');
                return $this->redirectToRoute('app_commande_panier');
            }

            $clientLon = $dataAdresse['features'][0]['geometry']['coordinates'][0];
            $clientLat = $dataAdresse['features'][0]['geometry']['coordinates'][1];

            // ÉTAPE B : Interroger l'API Géoplateforme Itinéraire officielle de l'IGN
            $urlIgn = "https://data.geopf.fr/navigation/itineraire";
            $payloadIgn = json_encode([
                "start" => "{$qgLon},{$qgLat}",
                "end" => "{$clientLon},{$clientLat}",
                "resource" => "bdtopo-osrm",
                "profile" => "car"
            ]);

            $options = [
                'http' => [
                    'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'method'  => 'POST',
                    'content' => $payloadIgn,
                    'timeout' => 5
                ],
            ];

            $context = stream_context_create($options);
            $responseGps = file_get_contents($urlIgn, false, $context);

            if ($responseGps === false) {
                $session->remove('livraison_calculee');
                $this->addFlash('danger', 'Une erreur technique est survenue lors du calcul via l\'IGN. Veuillez réessayer.');
                return $this->redirectToRoute('app_commande_panier');
            }

            $dataGps = json_decode($responseGps, true);

            if (!empty($dataGps['distance'])) {
                $distanceRouteKm = round($dataGps['distance'] / 1000, 1);
                
                $prixLivraison = 5.0 + ($distanceRouteKm * 0.59);

                $session->set('livraison_calculee', $prixLivraison);
                $this->addFlash('success', 'Frais logistiques calculés via l\'IGN : ' . $distanceRouteKm . ' km par la route.');
            } else {
                $session->remove('livraison_calculee');
                $this->addFlash('danger', 'Calcul de distance impossible pour cette destination.');
            }

        } catch (\Exception $e) {
            $session->remove('livraison_calculee');
            $this->addFlash('danger', 'Le service de calcul d\'itinéraire de l\'IGN est momentanément indisponible.');
        }

        return $this->redirectToRoute('app_commande_panier');
    }

    #[Route('/panier/valider', name: 'app_panier_valider', methods: ['POST'])]
    public function validerPanier(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, CommandeRepository $commandeRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $session = $request->getSession();
        $panierSession = $session->get('panier', []);

        if (empty($panierSession)) {
            $this->addFlash('danger', 'Votre panier est vide.');
            return $this->redirectToRoute('app_menu_index');
        }

        $prixLivraison = $session->get('livraison_calculee', null);

        if ($prixLivraison === null) {
            $this->addFlash('danger', 'Veuillez d\'abord calculer vos frais de livraison avant de valider.');
            return $this->redirectToRoute('app_commande_panier');
        }

        $datePrestationRaw = $request->request->get('datePrestation'); 
        $heureLivraison = $request->request->get('heureLivraison');
        $pretMateriel = $request->request->get('pretMateriel') === '1';

        $datePrestation = (new \DateTime($datePrestationRaw))->format('Y-m-d');
        $dateAujourdhui = (new \DateTime())->format('Y-m-d');
        if ($datePrestation < $dateAujourdhui) {
            $this->addFlash('danger', 'Erreur : Vous ne pouvez pas planifier une prestation pour une date passée !');
            return $this->redirectToRoute('app_commande_panier');
        }

        $commandesDuMemeJour = $commandeRepository->findBy(['datePrestation' => $datePrestation]);
        if (count($commandesDuMemeJour) >= 1) {
            $this->addFlash('danger', 'Désolé, Julie et José ne sont plus disponibles pour une livraison le ' . (new \DateTime($datePrestationRaw))->format('d/m/Y') . '. Veuillez choisir une autre date.');
            return $this->redirectToRoute('app_commande_panier');
        }

        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setDateCommande((new \DateTime())->format('Y-m-d'));
        $commande->setDatePrestation($datePrestation);
        $commande->setHeureLivraison($heureLivraison);
        $commande->setPretMateriel($pretMateriel);
        $commande->setRestitutionMateriel(false);
        $commande->setStatut('En attente');
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setQuantite(1); 

        $totalMenusGlobal = 0.0;
        $totalPersonnesGlobal = 0;

        foreach ($panierSession as $menuId => $nbPersonnes) {
            $menu = $entityManager->getRepository(Menu::class)->find($menuId);
            if (!$menu) continue;

            $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);

            $prixBrutMenu = $menu->getPrixParPersonne() * $nbPersonnes;
            if ($nbPersonnes >= ($menu->getNombrePersonneMin() + 5)) {
                $prixBrutMenu = $prixBrutMenu * 0.90; 
            }

            $totalMenusGlobal += $prixBrutMenu;
            $totalPersonnesGlobal += $nbPersonnes;
            
            $commande->addMenu($menu);
        }

        $commande->setPrixMenu($totalMenusGlobal);
        $commande->setPrixLivraison($prixLivraison);
        $commande->setNombrePersonne($totalPersonnesGlobal);

        $entityManager->persist($commande);
        $entityManager->flush();

        $email = (new TemplatedEmail())
            ->from(new Address('admin@test.com', 'Vite & Gourmand'))
            ->to((string)$user->getEmail())
            ->subject('Confirmation de votre commande ' . $commande->getNumeroCommande())
            ->htmlTemplate('emails/confirmation_commande.html.twig')
            ->context([
                'user' => $user,
                'commande' => $commande
            ]);
        $mailer->send($email);

        $session->remove('panier');
        $session->remove('livraison_calculee');
        $session->remove('form_adresse');
        $session->remove('form_ville');
        $session->remove('form_cp');
        $session->remove('form_date');
        $session->remove('form_heure');

        $this->addFlash('success', 'Votre commande a bien été enregistrée ! Un mail de confirmation vous a été envoyé.');
        return $this->redirectToRoute('app_home');
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

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
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
}