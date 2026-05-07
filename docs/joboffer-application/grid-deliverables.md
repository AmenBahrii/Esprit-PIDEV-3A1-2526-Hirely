# Sprint 2 Grid - Job Offer / Application Scope

## Scope Covered
- `src/Entity/Joboffer.php`
- `src/Entity/Application.php`
- `src/Controller/JobofferController.php`
- `src/Controller/ApplicationController.php`
- `src/Form/JobofferType.php`
- `src/Form/ApplicationType.php`
- `src/Form/ApplicationReviewType.php`
- `src/Service/ApplicationNotificationMailer.php`
- `src/Service/ResumeAutofillService.php`
- `src/Service/ApplicationReviewAssistantService.php`
- `src/Service/JobofferApplicationRulesService.php`

## 1. Tests statiques (PhpStan)
- Workshop command used:
  - `vendor/bin/phpstan analyse -c phpstan.joboffer-application.neon`
- Config file:
  - `phpstan.joboffer-application.neon`
- Analysis level:
  - `5`
- Static corrections applied:
  1. Added precise Doctrine collection generics on `Joboffer`
  2. Added precise Doctrine collection generics on `Application`
  3. Reconciled `Application::$availabilityDate` with its non-null database mapping
  4. Added safe constructor initialization for `Application`
  5. Replaced PHPDoc-only authenticated user assumptions with real `instanceof Users` checks in `JobofferController`
  6. Replaced PHPDoc-only authenticated user assumptions with real `instanceof Users` checks in `ApplicationController`
  7. Simplified always-true/null checks inside `Application::validateBusinessRules()`

## 2. Tests unitaires
- Workshop style followed:
  - isolated business rules
  - plain `TestCase`
  - valid / invalid cases
- Test file:
  - `tests/Service/JobofferApplicationRulesServiceTest.php`
- Implemented tests:
  1. Recruiter owned job offer can be published
  2. Non recruiter cannot own job offer
  3. Internship cannot require more than two years
  4. Closed offer cannot receive application
  5. Application email must match candidate
  6. Candidate experience must meet requirement
  7. Pending candidate application can be deleted

## 3. DoctrineDoctor
- Installed bundle:
  - `ahmed-bhs/doctrine-doctor`
- Registered in:
  - `config/bundles.php`
- Configured in:
  - `config/packages/doctrine_doctor.yaml`
- What to inspect in profiler for this scope:
  - job offer index page
  - application index page
  - recruiter application show page
- Runtime improvements prepared for this scope:
  - applications index already uses a `LEFT JOIN` instead of repeated related-offer lookups
  - job offer search/filter/sort is delegated to Doctrine query builder instead of post-processing everything in PHP

## 4. Rapport de performance
- Report file:
  - `docs/joboffer-application/performance-report.md`
- Generation command:
  - `C:\tools\php-8.2.30-nts-Win32-vs16-x64\php.exe bin\console app:report:module-performance joboffer-application`
- Main before/after focus:
  - applications index
  - job offer search

## 5. Scénario et données de test
### User Story
As a `candidate`, I want to `apply to a job offer` so that `I can be considered by the recruiter`.

### Success case
- **Given** a candidate is logged in
- **And** the selected job offer is `Open`
- **And** the candidate has enough experience
- **When** the candidate submits the application with a valid resume
- **Then** the application is stored with status `pending`
- **And** the recruiter receives an email notification

### Failure case
- **Given** a candidate is logged in
- **And** the selected job offer is `Closed` or already applied to
- **When** the candidate tries to submit the application
- **Then** the application is rejected by the workflow rules
- **And** no invalid application is saved

### Test data
- Recruiter account: `recruiter@example.com`
- Candidate account: `candidate@example.com`
- Job offer: `Symfony Developer`, `CDI`, `Open`, salary `2500`, required experience `2`
- Application: expected salary `2200`, availability `2026-05-10`, experience years `3`

## 6. Maîtrise du sujet / argumentation
- Why query-based filtering/search:
  - better for performance and cleaner than filtering after fetch
- Why VichUploader:
  - real resume upload instead of fake local-path text
- Why AI services:
  - Groq helps autofill resume data and draft recruiter reviews
- Why dedicated workflow rules service:
  - easier to explain and test offer/application business rules separately from controllers

## 7. Quantité de travail / valeur ajoutée
- resume upload with `VichUploaderBundle`
- AI CV autofill
- AI recruiter review suggestion
- map-based location selection
- recruiter scoring and review notes
- email notifications for application events
- candidate delete-pending flow
