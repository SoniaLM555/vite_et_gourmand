<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]

#[Route('/role')]
final class RoleController extends AbstractController
{
    #[Route(name: 'app_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): Response
    {
        return $this->render('role/index.html.twig', [
            'roles' => $roleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_role_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        throw $this->createAccessDeniedException('Les rôles sont figés et ne peuvent pas être modifiés.');
    }

    #[Route('/{id}', name: 'app_role_show', methods: ['GET'])]
    public function show(Role $role): Response
    {
        return $this->render('role/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_role_edit', methods: ['GET', 'POST'])]
    public function edit(Role $role): Response
    {
        throw $this->createAccessDeniedException('Les rôles sont figés et ne peuvent pas être modifiés.');
    }

    #[Route('/{id}', name: 'app_role_delete', methods: ['POST'])]
    public function delete(Role $role): Response
    {
        throw $this->createAccessDeniedException('Les rôles sont figés et ne peuvent pas être modifiés.');
    }
}
