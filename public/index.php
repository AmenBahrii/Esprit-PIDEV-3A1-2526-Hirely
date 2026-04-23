<?php

<<<<<<< HEAD
use Symfony\Component\HttpFoundation\Request;
=======
>>>>>>> OnboardingCoordination
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

<<<<<<< HEAD
return function (array $context) {
=======
return static function (array $context) {
>>>>>>> OnboardingCoordination
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
