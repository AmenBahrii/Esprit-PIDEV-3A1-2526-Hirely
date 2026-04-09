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
        '/interviews/new' => [[['_route' => 'app_interview_new', '_controller' => 'App\\Controller\\InterviewController::new'], null, ['GET' => 0, 'POST' => 1], null, false, false, null]],
        '/login' => [[['_route' => 'app_login', '_controller' => 'App\\Controller\\LoginController::login'], null, null, null, false, false, null]],
        '/logout' => [[['_route' => 'app_logout', '_controller' => 'App\\Controller\\LoginController::logout'], null, null, null, false, false, null]],
        '/' => [[['_route' => 'app_home', '_controller' => 'App\\Controller\\LoginController::index'], null, null, null, false, false, null]],
        '/recruiter-dashboard' => [[['_route' => 'app_dashboard', '_controller' => 'App\\Controller\\RecruiterDashboardController::index'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/evaluations/(?'
                    .'|interview/([^/]++)/form(*:46)'
                    .'|([^/]++)(?'
                        .'|(*:64)'
                        .'|/(?'
                            .'|edit(*:79)'
                            .'|delete(*:92)'
                        .')'
                    .')'
                .')'
                .'|/interviews/([^/]++)(?'
                    .'|(*:125)'
                    .'|/(?'
                        .'|edit(*:141)'
                        .'|delete(*:155)'
                        .'|complete(*:171)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        46 => [[['_route' => 'app_evaluation_form', '_controller' => 'App\\Controller\\EvaluationController::form'], ['interviewId'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        64 => [[['_route' => 'app_evaluation_show', '_controller' => 'App\\Controller\\EvaluationController::show'], ['id'], null, null, false, true, null]],
        79 => [[['_route' => 'app_evaluation_edit', '_controller' => 'App\\Controller\\EvaluationController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        92 => [[['_route' => 'app_evaluation_delete', '_controller' => 'App\\Controller\\EvaluationController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        125 => [[['_route' => 'app_interview_show', '_controller' => 'App\\Controller\\InterviewController::show'], ['id'], null, null, false, true, null]],
        141 => [[['_route' => 'app_interview_edit', '_controller' => 'App\\Controller\\InterviewController::edit'], ['id'], ['GET' => 0, 'POST' => 1], null, false, false, null]],
        155 => [[['_route' => 'app_interview_delete', '_controller' => 'App\\Controller\\InterviewController::delete'], ['id'], ['POST' => 0], null, false, false, null]],
        171 => [
            [['_route' => 'app_interview_complete', '_controller' => 'App\\Controller\\InterviewController::complete'], ['id'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
