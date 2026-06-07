<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(MenuRepository $menuRepository, AvisRepository $avisRepository): Response
    {
        $menus = $menuRepository->findBy([], ['prixParPersonne' => 'DESC'], 4);
        $avisAleatoires = $avisRepository->findRandomValidAvis();

        return $this->render('home/index.html.twig', [
            'menus' => $menus,
            'avis' => $avisAleatoires,
        ]);
    }
}
