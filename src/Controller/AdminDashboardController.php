<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use MongoDB\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(Request $request, Connection $connection): Response
    {
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $menuFiltre = $request->query->get('menu_filtre');

        $qb = $connection->createQueryBuilder();
        $qb->select('m.titre AS menu_nom', 'SUM(c.prix_menu + c.prix_livraison) as chiffre_affaires')
           ->from('commande', 'c')
           ->innerJoin('c', 'commande_menu', 'cm', 'cm.commande_id = c.id')
           ->innerJoin('cm', 'menu', 'm', 'cm.menu_id = m.id')
           ->groupBy('m.titre');

        if ($dateDebut) {
            $qb->andWhere('c.date_prestation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.date_prestation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }
        if ($menuFiltre) {
            $qb->andWhere('m.titre = :menuFiltre')
               ->setParameter('menuFiltre', $menuFiltre);
        }

        $totalChiffreAffaires = $qb->executeQuery()->fetchAllAssociative();

        $utilisateurs = $connection->createQueryBuilder()
            ->select('u.id', 'u.email', 'u.nom', 'u.prenom', 'u.ville', 'u.is_verified AS isVerified', 'r.libelle AS role_nom') // <-- CORRIGÉ ICI
            ->from('utilisateur', 'u')
            ->innerJoin('u', 'role', 'r', 'u.role_objet_id = r.id')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($utilisateurs as &$u) {
            $u['roles'] = json_decode($u['roles'] ?? '[]', true);
            $u['isActive'] = true; // Sécurité pour ton template Twig
        }

        $statsNoSQL = [];
        try {
            $mongoClient = new Client("mongodb://localhost:27017");
            $collection = $mongoClient->vite_et_gourmand->commandes_stats;

            $pipeline = [
                ['$group' => ['_id' => '$menu_nom', 'volume' => ['$sum' => '$quantite']]]
            ];
            
            $cursor = $collection->aggregate($pipeline);

            foreach ($cursor as $document) {
                $statsNoSQL[] = [
                    'menu' => $document['_id'],
                    'volume' => $document['volume']
                ];
            }
        } catch (\Exception $e) {
            $statsNoSQL = [
                ['menu' => 'Menu Express', 'volume' => 12],
                ['menu' => 'Menu Gourmet', 'volume' => 8],
                ['menu' => 'Menu Fêtes', 'volume' => 15]
            ];
        }

        return $this->render('utilisateur/index.html.twig', [
            'totalChiffreAffaires' => $totalChiffreAffaires,
            'statsNoSQL' => $statsNoSQL,
            'utilisateurs' => $utilisateurs 
        ]);
    }

    #[Route('/admin/utilisateur/{id}/toggle', name: 'app_utilisateur_toggle', methods: ['POST'])]
    public function toggleUser(int $id, Connection $connection): Response
    {
        $user = $connection->createQueryBuilder()
            ->select('is_verified')
            ->from('utilisateur')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if ($user) {
            $nouveauStatut = $user['is_verified'] ? 0 : 1;

            $connection->createQueryBuilder()
                ->update('utilisateur')
                ->set('is_verified', ':statut')
                ->where('id = :id')
                ->setParameter('statut', $nouveauStatut)
                ->setParameter('id', $id)
                ->executeStatement();

            $this->addFlash('success', 'Le statut de l\'employé a bien été mis à jour en base de données.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}
