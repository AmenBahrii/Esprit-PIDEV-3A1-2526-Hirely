# Hirely Web Application

Hirely is a recruitment and onboarding web platform built with Symfony. The project helps candidates discover opportunities, submit applications, and follow hiring progress, while recruiters and administrators manage job offers, applications, interviews, evaluations, onboarding plans, roles, and users from a unified workspace.

## Project Description

The web application was developed as part of an integrated academic project with a strong focus on:

- clean business workflows
- role-based access and navigation
- testing and code quality
- performance and query optimization
- user experience for both recruiters and candidates

Hirely combines recruitment features with onboarding coordination so the hiring journey continues smoothly after candidate selection.

## Main Modules

- User and role management
- Authentication and profile management
- Job offer management
- Application management
- Interview scheduling and evaluation
- Onboarding plans and task tracking
- Forum and collaboration area

## Key Features

- recruiter workspace for publishing and managing job offers
- candidate workspace for browsing offers and applying
- application review flow with recruiter notes and scores
- role-aware dashboards and navigation
- profile and account management
- onboarding planning after recruitment
- forum integration for interaction and collaboration
- PHPUnit tests and PHPStan static analysis
- DoctrineDoctor-based optimization and profiling work

## Technologies

- PHP
- Symfony 6.4
- Twig
- Doctrine ORM
- MySQL / MariaDB
- PHPUnit
- PHPStan
- DoctrineDoctor
- HTML / CSS / JavaScript

## Running the Project

### Requirements

- PHP 8.2+
- Composer
- Symfony CLI or a local PHP server
- MySQL or MariaDB

### Installation

```bash
composer install
```

### Environment

Configure your database and local environment in:

```bash
.env
```

### Database

Run migrations if needed:

```bash
php bin/console doctrine:migrations:migrate
```

### Start the Application

```bash
symfony server:start
```

or

```bash
php -S 127.0.0.1:8000 -t public
```

## Tests and Quality

### Unit Tests

```bash
php bin/phpunit
```

### Static Analysis

```bash
php vendor/bin/phpstan analyse -c phpstan.user-role.neon
php vendor/bin/phpstan analyse -c phpstan.joboffer-application.neon
```

## Repository Topics

Suggested repository topics:

- symfony
- php
- recruitment-platform
- onboarding
- job-offers
- applications
- integrated-project

## Keywords

Recruitment, onboarding, Symfony, PHP, web application, job offers, applications, interviews, evaluations, role management, personal branding, testing, optimization, team project.

## Authors

Developed as part of the Hirely integrated project team.
