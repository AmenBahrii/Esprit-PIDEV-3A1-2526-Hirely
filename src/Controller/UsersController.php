<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[Route('/users')]
final class UsersController extends AbstractController
{

    
    #[Route(name: 'app_users_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $entityManager
            ->getRepository(Users::class)
            ->findAll();

        return $this->render('users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
{
    //$this->denyAccessUnlessGranted('ROLE_ADMIN');
    $user = new Users();
    $form = $this->createForm(UsersType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔐 HASH PASSWORD
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );

        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_users_index');
    }

    return $this->render('users/new.html.twig', [
        'user' => $user,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_users_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Users $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
{
    //$this->denyAccessUnlessGranted('ROLE_ADMIN');
    $form = $this->createForm(UsersType::class, $user);
    $form->handleRequest($request);
    $originalPassword = $user->getPassword();
    if ($form->isSubmitted() && $form->isValid()) {

        if ($user->getPassword()) {
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);
    } else {
        $user->setPassword($originalPassword);
    }

    $em->flush();

        return $this->redirectToRoute('app_users_index');
    }

    return $this->render('users/edit.html.twig', [
        'user' => $user,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }
}
