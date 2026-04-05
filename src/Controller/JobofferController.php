<?php

namespace App\Controller;

use App\Entity\Joboffer;
use App\Entity\Users;
use App\Form\JobofferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/joboffer')]
final class JobofferController extends AbstractController
{
    #[Route(name: 'app_joboffer_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Joboffer::class);

        // 🔥 SIMULATED USER
         /** @var \App\Entity\Users $user */
        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName === 'admin') {
            $joboffers = $repo->findAll();

            return $this->render('admin/joboffer/index.html.twig', [
                'joboffers' => $joboffers,
                'roleName' => $roleName
            ]);
        }

        if ($roleName === 'recruiter') {
            $joboffers = $repo->findBy([
                'user' => $user
            ]);

            return $this->render('recruiter/joboffer/index.html.twig', [
                'joboffers' => $joboffers,
                'roleName' => $roleName
            ]);
        }

        // ✅ Candidate → only OPEN jobs
        $joboffers = $repo->findBy([
            'status' => 'Open'
        ]);

        return $this->render('candidate/joboffer/index.html.twig', [
            'joboffers' => $joboffers,
            'roleName' => $roleName
        ]);
    }

    #[Route('/new', name: 'app_joboffer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
         /** @var \App\Entity\Users $user */
        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        // ❌ Only recruiter allowed
        if ($roleName !== 'recruiter') {
            return $this->redirectToRoute('app_joboffer_index');
        }

        $joboffer = new Joboffer();
        $form = $this->createForm(JobofferType::class, $joboffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Assign recruiter automatically
            $joboffer->setUser($user);

            // ✅ Default status
            $joboffer->setStatus('Open');

            // ✅ Auto date (important)
            $joboffer->setPublicationDate(new \DateTime());

            $em->persist($joboffer);
            $em->flush();

            return $this->redirectToRoute('app_joboffer_index');
        }

        return $this->render('recruiter/joboffer/new.html.twig', [
            'joboffer' => $joboffer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_joboffer_show', methods: ['GET'])]
    public function show(Joboffer $joboffer, EntityManagerInterface $em): Response
    {
         /** @var \App\Entity\Users $user */
        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        // optional: different templates
        if ($roleName === 'recruiter') {
            return $this->render('recruiter/joboffer/show.html.twig', [
                'joboffer' => $joboffer,
            ]);
        }

        if ($roleName === 'admin') {
            return $this->render('admin/joboffer/show.html.twig', [
                'joboffer' => $joboffer,
            ]);
        }

        return $this->render('candidate/joboffer/show.html.twig', [
            'joboffer' => $joboffer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_joboffer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Joboffer $joboffer, EntityManagerInterface $em): Response
    {
         /** @var \App\Entity\Users $user */
        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
        $roleName = strtolower($user->getRole()?->getName() ?? '');
        $roleName = strtolower($user->getRole()->getName());

        if ($roleName !== 'recruiter' || $joboffer->getUser() !== $user) {
            return $this->redirectToRoute('app_joboffer_index');
        }

        $form = $this->createForm(JobofferType::class, $joboffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_joboffer_index');
        }

        return $this->render('recruiter/joboffer/edit.html.twig', [
            'joboffer' => $joboffer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_joboffer_delete', methods: ['POST'])]
    public function delete(Request $request, Joboffer $joboffer, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName !== 'recruiter' || $joboffer->getUser() !== $user) {
            return $this->redirectToRoute('app_joboffer_index');
        }

        if ($this->isCsrfTokenValid('delete'.$joboffer->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($joboffer);
            $em->flush();
        }

        return $this->redirectToRoute('app_joboffer_index');
    }
}