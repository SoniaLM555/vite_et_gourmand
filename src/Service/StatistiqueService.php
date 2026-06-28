<?php

namespace App\Service;

use App\Entity\Commande;
use MongoDB\Client;

class StatistiqueService
{

    private string $mongodbUri;
    private string $mongodbDb;

    public function __construct(string $mongodbUri, string $mongodbDb)
    {
        $this->mongodbUri = $mongodbUri;
        $this->mongodbDb = $mongodbDb;
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

        try {
            $client = new Client($this->mongodbUri);
            $collection = $client->selectDatabase($this->mongodbDb)->selectCollection('ventes');
            $collection->insertOne($document);
        } catch (\Exception $e) {
        }
    }

    public function findVente(string $numeroCommande): ?array
    {
        try {
            $client = new Client($this->mongodbUri);
            $collection = $client->selectDatabase($this->mongodbDb)->selectCollection('ventes');
            return $collection->findOne(['numero_commande' => $numeroCommande]);
        } catch (\Exception $e) {
            return null;
        }
    }
}