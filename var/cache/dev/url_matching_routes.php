<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/applications' => [[['_route' => 'app_applications', '_controller' => 'App\\Controller\\ApplicationController::list'], null, null, null, false, false, null]],
        '/applications/new' => [[['_route' => 'app_application_new', '_controller' => 'App\\Controller\\ApplicationController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/evaluations' => [[['_route' => 'app_evaluations', '_controller' => 'App\\Controller\\EvaluationController::list'], null, null, null, false, false, null]],
        '/interviews' => [[['_route' => 'app_interviews', '_controller' => 'App\\Controller\\InterviewController::list'], null, null, null, false, false, null]],
        '/interviews/new' => [[['_route' => 'app_interview_new', '_controller' => 'App\\Controller\\InterviewController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\LoginController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'app_logout', '_controller' => 'App\\Controller\\LoginController::logout'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_home', '_controller' => 'App\\Controller\\LoginController::index'], null, null, null, false, false, null]],
        '/recruiter-dashboard' => [[['_route' => 'app_dashboard', '_controller' => 'App\\Controller\\RecruiterDashboardController::index'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/applications/([^/]++)(?'
                    .'|(*:32)'
                    .'|/(?'
                        .'|edit(*:47)'
                        .'|delete(*:60)'
                    .')'
                .')'
                .'|/evaluations/(?'
                    .'|interview/([^/]++)/form(*:108)'
                    .'|([^/]++)(?'
                        .'|(*:127)'
                        .'|/(?'
                            .'|edit(*:143)'
                            .'|delete(*:157)'
                        .')'
                    .')'
                .')'
                .'|/interviews/([^/]++)(?'
                    .'|(*:191)'
                    .'|/(?'
                        .'|edit(*:207)'
                        .'|delete(*:221)'
                        .'|complete(*:237)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        32 => [[['_route' => 'app_application_show', '_controller' => 'App\\Controller\\ApplicationController::show'], ['id'], null, null, false, true, null]],
        47 => [[['_route' => 'app_application_edit', '_controller' => 'App\\Controller\\ApplicationController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        60 => [[['_route' => 'app_application_delete', '_controller' => 'App\\Controller\\ApplicationController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        108 => [[['_route' => 'app_evaluation_form', '_controller' => 'App\\Controller\\EvaluationController::form'], ['interviewId'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        127 => [[['_route' => 'app_evaluation_show', '_controller' => 'App\\Controller\\EvaluationController::show'], ['id'], null, null, false, true, null]],
        143 => [[['_route' => 'app_evaluation_edit', '_controller' => 'App\\Controller\\EvaluationController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        157 => [[['_route' => 'app_evaluation_delete', '_controller' => 'App\\Controller\\EvaluationController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        191 => [[['_route' => 'app_interview_show', '_controller' => 'App\\Controller\\InterviewController::show'], ['id'], null, null, false, true, null]],
        207 => [[['_route' => 'app_interview_edit', '_controller' => 'App\\Controller\\InterviewController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        221 => [[['_route' => 'app_interview_delete', '_controller' => 'App\\Controller\\InterviewController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        237 => [
            [['_route' => 'app_interview_complete', '_controller' => 'App\\Controller\\InterviewController::complete'], ['id'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
