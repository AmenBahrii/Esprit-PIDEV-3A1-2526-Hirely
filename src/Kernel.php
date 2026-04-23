<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
<<<<<<< HEAD
use Symfony\Component\DependencyInjection\ContainerBuilder;
=======
>>>>>>> OnboardingCoordination
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
<<<<<<< HEAD

    protected function configureContainer(ContainerBuilder $c): void
    {
        $c->setParameter('container.autowiring.strict_mode', true);
    }
=======
>>>>>>> OnboardingCoordination
}
