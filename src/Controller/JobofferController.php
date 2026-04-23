<?php

namespace App\Controller;

use App\Entity\Joboffer;
use App\Entity\User;
use App\Form\JobofferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JobofferController extends AbstractController
{
    #[Route('/admin/joboffers', name: 'app_admin_joboffer_index', methods: ['GET'])]
    public function adminIndex(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->renderJobofferIndex($request, $entityManager, $user, true);
    }

    #[Route('/workspace/joboffers', name: 'app_workspace_joboffer_index', methods: ['GET'])]
    public function workspaceIndex(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_RECRUITER') && !$this->isGranted('ROLE_CANDIDATE')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->renderJobofferIndex($request, $entityManager, $user, false);
    }

    #[Route('/workspace/joboffers/new', name: 'app_workspace_joboffer_new', methods: ['GET', 'POST'])]
    public function workspaceNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_RECRUITER');

        $joboffer = new Joboffer();
        $joboffer->setUser($user);
        $joboffer->setPublicationDate(new \DateTime());
        $joboffer->setStatus('Open');

        $form = $this->createForm(JobofferType::class, $joboffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $joboffer->setUser($user);
            $joboffer->setPublicationDate(new \DateTime());

            if (!$joboffer->getStatus()) {
                $joboffer->setStatus('Open');
            }

            $entityManager->persist($joboffer);
            $entityManager->flush();

            $this->addFlash('success', 'Job offer created successfully.');

            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        return $this->renderJobofferPage('joboffer/new.html.twig', [
            'form' => $form->createView(),
            'joboffer' => $joboffer,
            'topbar_title' => 'Create Job Offer',
        ]);
    }

    #[Route('/admin/joboffers/{id}', name: 'app_admin_joboffer_show', methods: ['GET'])]
    public function adminShow(Joboffer $joboffer): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->renderJobofferPage('admin/joboffer/show.html.twig', [
            'joboffer' => $joboffer,
            'role_name' => 'admin',
            'can_edit' => false,
            'can_apply' => false,
            'topbar_title' => 'Job Offer Details',
        ]);
    }

    #[Route('/workspace/joboffers/{id}', name: 'app_workspace_joboffer_show', methods: ['GET'])]
    public function workspaceShow(Joboffer $joboffer): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roleName = strtolower((string) $user->getRole()?->getName());

        if ('recruiter' === $roleName && $joboffer->getUser()?->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        if ('candidate' === $roleName && 'Open' !== $joboffer->getStatus()) {
            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        return $this->renderJobofferPage('joboffer/show.html.twig', [
            'joboffer' => $joboffer,
            'role_name' => $roleName,
            'can_edit' => 'recruiter' === $roleName && $joboffer->getUser()?->getId() === $user->getId(),
            'can_apply' => 'candidate' === $roleName && 'Open' === $joboffer->getStatus(),
            'topbar_title' => 'Job Offer Details',
        ]);
    }

    #[Route('/workspace/joboffers/{id}/edit', name: 'app_workspace_joboffer_edit', methods: ['GET', 'POST'])]
    public function workspaceEdit(Request $request, Joboffer $joboffer, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_RECRUITER') || $joboffer->getUser()?->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        $form = $this->createForm(JobofferType::class, $joboffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Job offer updated successfully.');

            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        return $this->renderJobofferPage('joboffer/edit.html.twig', [
            'form' => $form->createView(),
            'joboffer' => $joboffer,
            'topbar_title' => 'Edit Job Offer',
        ]);
    }

    #[Route('/admin/joboffers/{id}', name: 'app_admin_joboffer_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Joboffer $joboffer, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $joboffer->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($joboffer);
            $entityManager->flush();
            $this->addFlash('success', 'Job offer deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_joboffer_index');
    }

    #[Route('/workspace/joboffers/{id}', name: 'app_workspace_joboffer_delete', methods: ['POST'])]
    public function workspaceDelete(Request $request, Joboffer $joboffer, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $canDelete = $this->isGranted('ROLE_RECRUITER') && $joboffer->getUser()?->getId() === $user->getId();

        if (!$canDelete) {
            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        if ($this->isCsrfTokenValid('delete' . $joboffer->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($joboffer);
            $entityManager->flush();
            $this->addFlash('success', 'Job offer deleted successfully.');
        }

        return $this->redirectToRoute('app_workspace_joboffer_index');
    }

    private function renderJobofferIndex(
        Request $request,
        EntityManagerInterface $entityManager,
        User $user,
        bool $adminArea
    ): Response {
        $roleName = strtolower((string) $user->getRole()?->getName());
        $search = trim((string) $request->query->get('search', ''));
        $sort = trim((string) $request->query->get('sort', 'date_desc'));

        $qb = $entityManager->getRepository(Joboffer::class)->createQueryBuilder('j');

        if ($adminArea) {
            // admin sees all job offers
        } elseif ('recruiter' === $roleName) {
            $qb->andWhere('j.user = :user')->setParameter('user', $user);
        } else {
            $qb->andWhere('j.status = :status')->setParameter('status', 'Open');
        }

        if ('' !== $search) {
            $qb
                ->andWhere('j.title LIKE :search OR j.location LIKE :search OR j.contractType LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        switch ($sort) {
            case 'salary_asc':
                $qb->orderBy('j.salary', 'ASC');
                break;
            case 'salary_desc':
                $qb->orderBy('j.salary', 'DESC');
                break;
            case 'experience_asc':
                $qb->orderBy('j.experienceRequired', 'ASC');
                break;
            case 'experience_desc':
                $qb->orderBy('j.experienceRequired', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('j.publicationDate', 'ASC');
                break;
            default:
                $qb->orderBy('j.publicationDate', 'DESC');
        }

        return $this->renderJobofferPage(
            $adminArea ? 'admin/joboffer/index.html.twig' : 'joboffer/index.html.twig',
            [
                'joboffers' => $qb->getQuery()->getResult(),
                'search' => $search,
                'sort' => $sort,
                'role_name' => $roleName,
                'can_manage_joboffers' => $adminArea || 'recruiter' === $roleName,
                'can_create_joboffers' => !$adminArea && 'recruiter' === $roleName,
                'topbar_title' => 'Job Offers',
            ]
        );
    }

    private function renderJobofferPage(string $template, array $data = []): Response
    {
        return $this->render($template, array_merge(
            [
                'topbar_subtitle' => 'Recruitment and onboarding workspace',
            ],
            $data
        ));
    }
}
