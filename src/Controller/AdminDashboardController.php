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

        if ($dateDebut) $qb->andWhere('c.date_prestation >= :debut')->setParameter('debut', $dateDebut);
        if ($dateFin) $qb->andWhere('c.date_prestation <= :fin')->setParameter('fin', $dateFin);
        if ($menuFiltre) $qb->andWhere('m.titre = :menu')->setParameter('menu', $menuFiltre);

        $totalChiffreAffaires = $qb->executeQuery()->fetchAllAssociative();

        $utilisateurs = $connection->createQueryBuilder()
            ->select('u.id', 'u.email', 'u.nom', 'u.prenom', 'u.ville', 'u.is_verified AS isVerified', 'r.libelle AS role_nom', 'u.roles')
            ->from('utilisateur', 'u')
            ->innerJoin('u', 'role', 'r', 'u.role_objet_id = r.id')
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($utilisateurs as &$u) { $u['roles'] = json_decode($u['roles'] ?? '[]', true); $u['isActive'] = true; }

        $tousLesMenus = $connection->createQueryBuilder()->select('titre')->from('menu')->orderBy('titre', 'ASC')->executeQuery()->fetchAllAssociative();

        $caTotalNoSQL = 0; $statsNoSQL = []; $caParMenuNoSQL = [];
        try {
            $mongoClient = new Client($_ENV['MONGODB_URL']);
            $collection = $mongoClient->selectDatabase($_ENV['MONGODB_DB'])->selectCollection('ventes');

            $match = [];
            if ($menuFiltre) {
                $match['items.menu_nom'] = $menuFiltre;
            }
            if ($dateDebut || $dateFin) {
                $match['date_commande'] = [];
                if ($dateDebut) $match['date_commande']['$gte'] = $dateDebut;
                if ($dateFin) $match['date_commande']['$lte'] = $dateFin;
            }

            $pipelineMatch = !empty($match) ? ['$match' => $match] : null;
            
            $matchItem = $menuFiltre ? ['$match' => ['items.menu_nom' => $menuFiltre]] : null;

            $stagesVolume = [['$unwind' => '$items']];
            if ($matchItem) $stagesVolume[] = $matchItem; 
            $stagesVolume[] = ['$group' => ['_id' => '$items.menu_nom', 'volume' => ['$sum' => '$items.quantite']]];
            
            if ($pipelineMatch) array_unshift($stagesVolume, $pipelineMatch);
            
            $cursorVolume = $collection->aggregate($stagesVolume);
            foreach ($cursorVolume as $doc) { $statsNoSQL[] = ['menu' => $doc['_id'], 'volume' => $doc['volume']]; }

            $stagesCA = [['$unwind' => '$items']];
            if ($matchItem) $stagesCA[] = $matchItem; 
            $stagesCA[] = ['$group' => ['_id' => '$items.menu_nom', 'ca_menu' => ['$sum' => ['$multiply' => ['$items.prix_menu_unitaire', '$items.quantite']]]]];
            
            if ($pipelineMatch) array_unshift($stagesCA, $pipelineMatch);
            
            $cursorCAparMenu = $collection->aggregate($stagesCA);
            foreach ($cursorCAparMenu as $doc) { $caParMenuNoSQL[] = ['menu' => $doc['_id'], 'ca' => $doc['ca_menu']]; }

            $pipelineTotal = $pipelineMatch ? [$pipelineMatch] : [];
            $pipelineTotal[] = ['$group' => ['_id' => null, 'total_ca' => ['$sum' => ['$add' => ['$total_commande_hors_livraison', '$frais_livraison']]]]];
            
            $cursorCA = $collection->aggregate($pipelineTotal)->toArray();
            if (!empty($cursorCA)) { $caTotalNoSQL = $cursorCA[0]['total_ca']; }

        } catch (\Exception $e) {
        }
        

                return $this->render('utilisateur/index.html.twig', [
                    'totalChiffreAffaires' => $totalChiffreAffaires, 'caTotalNoSQL' => $caTotalNoSQL, 
                    'caParMenuNoSQL' => $caParMenuNoSQL, 'statsNoSQL' => $statsNoSQL,
                    'utilisateurs' => $utilisateurs, 'tousLesMenus' => $tousLesMenus
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

            $this->addFlash('success', 'Le statut de l\'utilisateur a bien été mis à jour.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }
}