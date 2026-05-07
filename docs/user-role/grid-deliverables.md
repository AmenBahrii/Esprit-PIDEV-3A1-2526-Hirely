# Sprint 2 Grid - User / Role Scope

## Scope Covered
- `src/Entity/Users.php`
- `src/Entity/Role.php`
- `src/Controller/UsersController.php`
- `src/Controller/RoleController.php`
- `src/Form/UsersType.php`
- `src/Form/RoleType.php`
- `src/Service/UserRoleSummaryService.php`
- `src/Service/UserRoleRulesService.php`

## 1. Tests statiques (PhpStan)
- Workshop command used:
  - `vendor/bin/phpstan analyse -c phpstan.user-role.neon`
- Config file:
  - `phpstan.user-role.neon`
- Analysis level:
  - `5`
- Static corrections applied:
  1. Added precise Doctrine collection generics on `Users`
  2. Added precise Doctrine collection generics on `Role`
  3. Initialized all `Users` collections in the constructor
  4. Replaced PHPDoc-only authenticated user assumptions with real `instanceof Users` checks where needed
  5. Kept role/status summary logic type-safe in `UserRoleSummaryService`
  6. Added a dedicated business-rules service `UserRoleRulesService` with typed returns and predictable exceptions

## 2. Tests unitaires
- Workshop style followed:
  - isolated business rules
  - plain `TestCase`
  - clear valid / invalid scenarios
- Test file:
  - `tests/Service/UserRoleRulesServiceTest.php`
- Implemented tests:
  1. Active role can be assigned
  2. Inactive role cannot be assigned
  3. Active role needs dashboard
  4. Google linked user can skip password
  5. Local user needs password
  6. Short password is rejected
  7. Admin can edit another user

## 3. DoctrineDoctor
- Installed bundle:
  - `ahmed-bhs/doctrine-doctor`
- Registered in:
  - `config/bundles.php`
- Configured in:
  - `config/packages/doctrine_doctor.yaml`
- What to check in profiler for this scope:
  - admin users page
  - role index page
- Runtime improvements prepared for this scope:
  - grouped user stats are computed in SQL
  - Google-linked and face-enabled counts are computed with focused count queries

## 4. Rapport de performance
- Report file:
  - `docs/user-role/performance-report.md`
- Generation command:
  - `C:\tools\php-8.2.30-nts-Win32-vs16-x64\php.exe bin\console app:report:module-performance user-role`
- Main before/after focus:
  - admin user summary
  - user directory filtering

## 5. Scénario et données de test
### User Story
As an `admin`, I want to `manage users and roles` so that `the platform keeps the right permissions and account status`.

### Success case
- **Given** an admin is logged in
- **And** the role `Recruiter` is active with a default dashboard
- **When** the admin creates or edits a user with that role and valid credentials
- **Then** the user is saved successfully
- **And** the user receives the expected security role mapping

### Failure case
- **Given** an admin is creating a local account without Google sign-in
- **When** the admin leaves the password empty
- **Then** the business rule rejects the account
- **And** the message explains that a non-Google account must define a password

### Test data
- Role `Recruiter`, status `active`, dashboard `recruiter_dashboard`
- Role `Candidate`, status `inactive`, dashboard `candidate_dashboard`
- User `admin@example.com`, active, role `Admin`
- User `candidate@example.com`, active, role `Candidate`

## 6. Maîtrise du sujet / argumentation
- Why Symfony validators:
  - centralize server-side rules on users and roles
- Why a dedicated rules service:
  - gives isolated workshop-style unit tests
  - keeps account and role rules easy to explain
- Why SQL summary queries:
  - better fit for dashboard statistics than loading all users and grouping in PHP

## 7. Quantité de travail / valeur ajoutée
- AI user/role summary on the admin page
- profile QR contact export
- role-based profile edit restrictions
- Google and Face ID account handling
- dedicated static analysis and unit tests for this scope
