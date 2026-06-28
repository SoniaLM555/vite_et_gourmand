<?php

namespace App\Service;

use App\Entity\Commande;
use MongoDB\Client;

class StatistiqueService
{
    private $collection;

    public function __construct(string $mongodbUri, string $mongodbDb)
    {
        $client = new Client($mongodbUri);
        $this->collection = $client->selectDatabase($mongodbDb)->selectCollection('ventes');
    }

    public function enregistrerVente(Commande $commande)
    {
        $items = [];
        $totalMenusNet = 0.0;
        $nbPersonnes = $commande->getNombrePersonne();

        foreach ($commande->getMenus() as $menu) {
            $prixUnitaire = $menu->getPrixParPersonne();
            $prixBrutLigne = $prixUnitaire * $nbPersonnes;

            $aDroitAuDixPourcent = $nbPersonnes >= ($menu->getNombrePersonneMin() + 5);
            $montantRemise = $aDroitAuDixPourcent ? ($prixBrutLigne * 0.10) : 0.0;
            $prixFinalPourCeMenu = $prixBrutLigne - $montantRemise;
            
            $totalMenusNet += $prixFinalPourCeMenu;

            $items[] = [
                'menu_id' => $menu->getId(),
                'menu_nom' => $menu->getTitre(),
                'quantite' => $nbPersonnes,
                'prix_menu_unitaire' => $prixUnitaire,
                'reduction_unitaire' => ($montantRemise / $nbPersonnes), 
                'total_ligne' => $prixFinalPourCeMenu
            ];
        }

        $document = [
            'numero_commande' => $commande->getNumeroCommande(),
            'statut' => mb_strtolower(trim($commande->getStatut())),
            'date_commande' => $commande->getDatePrestation(),
            'utilisateur_id' => $commande->getUtilisateur()->getId(),
            'total_commande_hors_livraison' => $totalMenusNet,
            'frais_livraison' => $commande->getPrixLivraison(),
            'total_final' => ($totalMenusNet + $commande->getPrixLivraison()),
            'items' => $items
        ];

        $this->collection->insertOne($document);
    }
}