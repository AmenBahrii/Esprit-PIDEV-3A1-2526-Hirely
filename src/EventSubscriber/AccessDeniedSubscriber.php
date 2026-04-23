<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$throwable instanceof AccessDeniedException && !$throwable instanceof AccessDeniedHttpException) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if (!$this->security->getUser()) {
            return;
        }

        $session = $request->getSession();
        if ($session) {
            $session->getFlashBag()->add('error', 'You do not have access to that page with this account.');
        }

        $targetRoute = $this->security->isGranted('ROLE_ADMIN') ? 'app_admin' : 'app_workspace';
        $event->setResponse(new RedirectResponse($this->urlGenerator->generate($targetRoute)));
    }
}
