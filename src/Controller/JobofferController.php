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
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Joboffer::class);

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');
        $search = $request->query->get('search');
        $sort = $request->query->get('sort');

        $qb = $repo->createQueryBuilder('j');

        if ($roleName === 'recruiter') {
            $qb->andWhere('j.user = :user')
                ->setParameter('user', $user);
        } elseif ($roleName !== 'admin') {
            $qb->andWhere('j.status = :status')
                ->setParameter('status', 'Open');
        }

        if ($search) {
            $qb->andWhere('j.title LIKE :search OR j.location LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        switch ($sort) {
            case 'salary_asc':
                $qb->orderBy('j.salary', 'ASC');
                break;
            case 'salary_desc':
                $qb->orderBy('j.salary', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('j.publicationDate', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('j.publicationDate', 'DESC');
                break;
            default:
                $qb->orderBy('j.id', 'DESC');
        }

        $joboffers = $qb->getQuery()->getResult();

        return $this->render($roleName . '/joboffer/index.html.twig', [
            'joboffers' => $joboffers,
            'search' => $search,
            'sort' => $sort,
            'roleName' => $roleName,
        ]);
    }

    #[Route('/new', name: 'app_joboffer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName !== 'recruiter') {
            return $this->redirectToRoute('app_joboffer_index');
        }

        $joboffer = new Joboffer();
        $joboffer->setUser($user);
        $joboffer->setPublicationDate(new \DateTime());
        $form = $this->createForm(JobofferType::class, $joboffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $joboffer->setStatus('Open');
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
    public function show(Joboffer $joboffer): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName === 'recruiter') {
            if ($joboffer->getUser() !== $user) {
                return $this->redirectToRoute('app_joboffer_index');
            }

            return $this->render('recruiter/joboffer/show.html.twig', [
                'joboffer' => $joboffer,
            ]);
        }

        if ($roleName === 'admin') {
            return $this->render('admin/joboffer/show.html.twig', [
                'joboffer' => $joboffer,
            ]);
        }

        if ($joboffer->getStatus() !== 'Open') {
            return $this->redirectToRoute('app_joboffer_index');
        }

        return $this->render('candidate/joboffer/show.html.twig', [
            'joboffer' => $joboffer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_joboffer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Joboffer $joboffer, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');

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
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

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
