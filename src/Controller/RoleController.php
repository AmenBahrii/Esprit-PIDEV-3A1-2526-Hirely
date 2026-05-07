<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/role')]
final class RoleController extends AbstractController
{
    #[Route(name: 'app_role_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $roles = $entityManager->getRepository(Role::class)->findAll();

        return $this->render('role/index.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/new', name: 'app_role_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/new.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_role_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Role $role): Response
    {
        return $this->render('role/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_role_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('role/edit.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_role_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Role $role, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($role);
            $em->flush();
        }

        return $this->redirectToRoute('app_role_index');
    }
}
