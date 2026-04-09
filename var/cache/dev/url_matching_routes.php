<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/evaluations' => [[['_route' => 'app_evaluations', '_controller' => 'App\\Controller\\EvaluationController::list'], null, null, null, false, false, null]],
        '/interviews' => [[['_route' => 'app_interviews', '_controller' => 'App\\Controller\\InterviewController::list'], null, null, null, false, false, null]],
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\LoginController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'app_logout', '_controller' => 'App\\Controller\\LoginController::logout'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_home', '_controller' => 'App\\Controller\\LoginController::index'], null, null, null, false, false, null]],
        '/recruiter-dashboard' => [[['_route' => 'app_dashboard', '_controller' => 'App\\Controller\\RecruiterDashboardController::index'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/evaluations/(?'
                    .'|([^/]++)(*:31)'
                    .'|interview/([^/]++)/form(*:61)'
                    .'|submit(*:74)'
                .')'
                .'|/interviews/([^/]++)(?'
                    .'|(*:105)'
                    .'|/complete(*:122)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        31 => [[['_route' => 'app_evaluation_show', '_controller' => 'App\\Controller\\EvaluationController::show'], ['id'], null, null, false, true, null]],
        61 => [[['_route' => 'app_evaluation_form', '_controller' => 'App\\Controller\\EvaluationController::form'], ['interviewId'], null, null, false, false, null]],
        74 => [[['_route' => 'app_evaluation_submit', '_controller' => 'App\\Controller\\EvaluationController::submit'], [], ['POST' => 0], null, false, false, null]],
        105 => [[['_route' => 'app_interview_show', '_controller' => 'App\\Controller\\InterviewController::show'], ['id'], null, null, false, true, null]],
        122 => [
            [['_route' => 'app_interview_complete', '_controller' => 'App\\Controller\\InterviewController::complete'], ['id'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
