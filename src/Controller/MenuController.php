<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ThemeRepository; 
use App\Repository\RegimeRepository; 
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted; 

#[Route('/menu')]
final class MenuController extends AbstractController
{

    #[Route(name: 'app_menu_index', methods: ['GET'])]
    public function index(Request $request, MenuRepository $menuRepository, ThemeRepository $themeRepository, RegimeRepository $regimeRepository, PaginatorInterface $paginator): Response 
    {
        $themeId     = $request->query->get('theme');
        $regimeId    = $request->query->get('regime');
        $nbPersonnes = $request->query->get('nbPersonnes');
        $prixMax     = $request->query->get('prixMax');

        $themeId     = $themeId === '' ? null : (int)$themeId;
        $regimeId    = $regimeId === '' ? null : (int)$regimeId;
        $nbPersonnes = $nbPersonnes === '' ? null : (int)$nbPersonnes;
        $prixMax     = $prixMax === '' ? null : (float)$prixMax;

        $queryBuilder = $menuRepository->createQueryBuilderForFilters($themeId, $regimeId, $nbPersonnes, $prixMax);
        $pagination   = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 4);

        return $this->render('menu/index.html.twig', [
            'menus'   => $pagination,
            'themes'  => $themeRepository->findAll(),
            'regimes' => $regimeRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($menu);
            $entityManager->flush();

            return $this->redirectToRoute('app_menu_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_menu_show', methods: ['GET'])]
    public function show(Menu $menu): Response
    {
        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/edit', name: 'app_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_menu_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($menu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_menu_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/liste', name: 'app_menu_admin_index', methods: ['GET'])]
    public function adminIndex(MenuRepository $menuRepository): Response
    {
        return $this->render('menu/admin_index.html.twig', [
            'menus' => $menuRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/{id}', name: 'app_menu_admin_show', methods: ['GET'])]
    public function adminShow(Menu $menu): Response
    {
        return $this->render('menu/admin_show.html.twig', [
            'menu' => $menu,
        ]);
    }
}
