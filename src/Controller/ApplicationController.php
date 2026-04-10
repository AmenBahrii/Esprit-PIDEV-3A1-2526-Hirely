<?php

namespace App\Controller;

use App\Entity\Application;
use App\Form\ApplicationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Users;

#[Route('/application')]
final class ApplicationController extends AbstractController
{
    #[Route('/{applicationId}/accept', name: 'app_application_accept')]
public function accept(Application $application, EntityManagerInterface $em): Response
{
    // 🔥 TEMP user simulation (same as index)
    /** @var \App\Entity\Users $user */
$user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}

$roleName = strtolower($user->getRole()?->getName() ?? '');

    // ❌ Only recruiter allowed
    if ($roleName !== 'recruiter') {
    return $this->redirectToRoute('app_application_index');
}

    // ❌ Check ownership (VERY IMPORTANT)
    if ($application->getJobOffer()->getUser() !== $user) {
        return $this->redirectToRoute('app_application_index');
    }
    if ($application->getJobOffer()->getUser() !== $user) {
    return $this->redirectToRoute('app_application_index');
}

// ✅ NEW: prevent re-processing
if (strtolower($application->getCurrentStatus()) !== 'pending') {
    return $this->redirectToRoute('app_application_index');
}

$application->setCurrentStatus('Accepted');
$application->setLastUpdateDate(new \DateTime());

$em->flush();

    // ✅ Update status
    $application->setCurrentStatus('Accepted');

    $em->flush();

    $this->addFlash('success', 'Application accepted');

    return $this->redirectToRoute('app_application_index');
}

#[Route('/{applicationId}/reject', name: 'app_application_reject')]
public function reject(Application $application, EntityManagerInterface $em): Response
{
    /** @var \App\Entity\Users $user */
$user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}

$roleName = strtolower($user->getRole()?->getName() ?? '');

    if ($roleName !== 'recruiter') {
    return $this->redirectToRoute('app_application_index');
}

    if ($application->getJobOffer()->getUser() !== $user) {
        return $this->redirectToRoute('app_application_index');
    }

    $application->setCurrentStatus('Rejected');

    $em->flush();

    $this->addFlash('error', 'Application rejected');

    return $this->redirectToRoute('app_application_index');
}


   #[Route(name: 'app_application_index', methods: ['GET'])]
public function index(EntityManagerInterface $em): Response
{
    $repo = $em->getRepository(Application::class);

    // 🔥 TEMP USER SIMULATION
   /** @var \App\Entity\Users $user */
$user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}

$roleName = strtolower($user->getRole()?->getName() ?? '');

    if ($roleName === 'admin') {
        $applications = $repo->findAll();

        return $this->render('admin/application/index.html.twig', [
            'applications' => $applications
        ]);
    }

    if ($roleName === 'recruiter') {
        $applications = $repo->createQueryBuilder('a')
            ->join('a.jobOffer', 'j')
            ->where('j.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return $this->render('recruiter/application/index.html.twig', [
            'applications' => $applications
        ]);
    }

    // Candidate
    $applications = $repo->findBy([
        'user' => $user
    ]);

    return $this->render('candidate/application/index.html.twig', [
        'applications' => $applications
    ]);
}
    

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    // ✅ Only candidates can apply
    if (!$this->isGranted('ROLE_CANDIDATE')) {
        throw $this->createAccessDeniedException();
    }

    $application = new Application();
    $form = $this->createForm(ApplicationType::class, $application);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $user = $this->getUser();

if (!$user) {
    return $this->redirectToRoute('app_login');
}
    
        $jobOffer = $application->getJobOffer();

        // ✅ 1. Prevent duplicate application
        $existing = $entityManager->getRepository(Application::class)->findOneBy([
            'user' => $user,
            'jobOffer' => $jobOffer
        ]);

        if ($existing) {
            $this->addFlash('error', 'You already applied to this job!');
            return $this->redirectToRoute('app_joboffer_index');
        }

        // ✅ 2. Check job is open
        if ($jobOffer->getStatus() !== 'Open') {
            $this->addFlash('error', 'This job is closed!');
            return $this->redirectToRoute('app_joboffer_index');
        }

        // ✅ 3. Assign user automatically
        $application->setUser($user);

        // ✅ Save
        $entityManager->persist($application);
        $entityManager->flush();

        $this->addFlash('success', 'Application submitted successfully!');

        return $this->redirectToRoute('app_application_index');
    }

    return $this->render('candidate/application/new.html.twig', [
        'application' => $application,
        'form' => $form,
    ]);
}
    
    #[Route('/{applicationId}', name: 'app_application_show', methods: ['GET'])]
    public function show(Application $application): Response
    {
        return $this->render('application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{applicationId}/edit', name: 'app_application_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('application/edit.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{applicationId}', name: 'app_application_delete', methods: ['POST'])]
    public function delete(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$application->getApplicationId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }
}
