# S3 Grading Focus - Forum Module

Primary source: `Grille S3.xlsx`.

This week, the main focus is Grille S3: static analysis, unit tests, DoctrineDoctor, a performance report, clear test scenarios, and realistic test data. Gemini is only mentioned briefly because it was old missed work that has now been verified; it is not the main S3 deliverable.

## What S3 Asks For

| Criterion | Evidence in this project |
|---|---|
| Static analysis / PHPStan | `php tools/forum_static_checks.php` has 8 custom checks, and `vendor/bin/phpstan analyse -c phpstan.neon.dist` passes. |
| Unit tests | `php tests/run_forum_unit_tests.php` has 8 focused forum unit tests. |
| DoctrineDoctor | Composer-installed and registered as a Symfony web-profiler data collector. |
| Performance report | `docs/forum-performance-report.md` has measured commands and database volume. |
| Scenario and test data | `docs/forum-test-scenarios.md` plus `src/DataFixtures/ForumFixtures.php`. |
| Added value | Moderation, notifications, likes, pagination, admin moderation, anti-spam, Python AI portability/fallback, AI scoring paths, plus a brief verified Gemini smoke check. |
| Collaborative integration | `docs/integration/team-integration-report.md` documents the v1/v2/v3/hirely4 comparison and the safe integration into one runnable Symfony app. |
| Oral defense | Commands below are repeatable and explainable. |

## Composer And SSL State

Composer HTTPS works normally again after Avast was temporarily disabled. TLS was not disabled.

Current Composer/PHP certificate settings:

- `disable-tls`: `false`
- `secure-http`: `true`
- `cafile`: `C:/xampp/php/extras/ssl/cacert.pem`
- `capath`: empty
- PHP ini: `C:\xampp\php\php.ini`

No manual downloaded bundle folders were restored. Bundle cleanup is project hygiene and dependency management: vendor code must be managed by Composer, not copied by hand.

## Manual Bundle Cleanup Replaced By Composer

The removed manual folders were:

- `DoctrineDataFixtures-2.2.x`
- `DoctrineFixturesBundle-4.3.x`
- `antispam-bundle-master`
- `KnpPaginatorBundle-master`

All four equivalents are now Composer-managed:

| Removed manual folder | Composer-managed equivalent | Verification |
|---|---|---|
| `KnpPaginatorBundle-master` | `knplabs/knp-paginator-bundle` 6.10.0 | `composer show knplabs/knp-paginator-bundle` |
| `antispam-bundle-master` | `omines/antispam-bundle` 0.1.10 | `composer show omines/antispam-bundle` |
| `DoctrineFixturesBundle-4.3.x` | `doctrine/doctrine-fixtures-bundle` 4.3.1 | `composer show doctrine/doctrine-fixtures-bundle` |
| `DoctrineDataFixtures-2.2.x` | `doctrine/data-fixtures` 2.2.1 | `composer show doctrine/data-fixtures` |

`doctrine/data-fixtures` is installed automatically as a dependency of `doctrine/doctrine-fixtures-bundle`, because the Symfony fixtures bundle requires the underlying Doctrine data fixtures library.

## Composer Packages Installed For S3

- `knplabs/knp-paginator-bundle` 6.10.0
- `omines/antispam-bundle` 0.1.10
- `doctrine/doctrine-fixtures-bundle` 4.3.1
- `doctrine/data-fixtures` 2.2.1
- `phpstan/phpstan` 2.1.54
- `phpstan/phpstan-symfony` 2.0.15
- `phpstan/phpstan-doctrine` 2.0.21
- `ahmed-bhs/doctrine-doctor` 0.1.0-alpha.3

DoctrineDoctor is pinned to `v0.1.0-alpha.3` because newer releases require PHP 8.2 or PHP 8.4, while this project currently runs PHP 8.1.25.

Security audit was also cleaned by patch-updating:

- `symfony/http-foundation` 6.4.35
- `symfony/process` 6.4.33

## Current Forum Coverage

- Public/user forum: post list, post details, post creation/editing/deletion, comments, likes, notifications, pagination and search.
- Moderation: AI scoring, status changes, locking, pinning, admin dashboard, admin feedback.
- AI/API: Perspective scoring, Python fallback scoring, Google Safe Browsing, and a brief verified Gemini reply path from older work.
- Python portability: dependencies are installed through `web/ai_service/requirements.txt`; setup scripts create a virtual environment; missing AI dependencies return an unavailable response instead of crashing the forum.
- Anti-spam: Composer-installed AntiSpamBundle profiles for posts and comments.
- Pagination: Composer-installed KnpPaginatorBundle for forum and admin lists.
- Test data: small realistic fixture set for users, roles, posts, comments, likes, notifications and moderation statuses.

## Commands That Prove S3

Run from `C:\Users\Firas\OneDrive\Desktop\hirely2\hirely\web\forum-site`.

```powershell
composer validate --no-check-publish
composer audit
composer show knplabs/knp-paginator-bundle
composer show omines/antispam-bundle
composer show doctrine/doctrine-fixtures-bundle
composer show doctrine/data-fixtures
php bin\console lint:container
php bin\console lint:yaml config
php bin\console lint:twig templates
php tools\forum_static_checks.php
php tests\run_forum_unit_tests.php
vendor\bin\phpstan analyse -c phpstan.neon.dist
php bin\console debug:container knp_paginator
php bin\console debug:config antispam
php bin\console list doctrine:fixtures
php bin\console doctrine:fixtures:load --help
php bin\console doctrine:fixtures:load --dry-run --append --no-interaction
php bin\console debug:config doctrine_doctor
php bin\console debug:container --tag=data_collector
python -m py_compile ..\ai_service\app.py
```

## Latest Results

- Composer validation: passed.
- Project Composer audit: passed, no project dependency advisories.
- Symfony container lint: passed.
- YAML lint: 18 valid files.
- Twig lint: 24 valid files.
- Custom static checks: 8 passed.
- Unit tests: 8 passed.
- PHPStan: passed at level 5, 46 files analysed, no ignored baseline.
- KnpPaginator service: registered.
- AntiSpam config: registered with forum post/comment profiles.
- Doctrine fixtures: bundle and data-fixtures library are installed; command is registered; `--help` works; dry-run with `--append` passes.
- DoctrineDoctor: config loads; `debug:container | findstr /i doctor` shows DoctrineDoctor services; `DoctrineDoctorDataCollector` is registered in the Symfony web profiler. This version has no CLI command or route.
- Python AI portability: `web/ai_service/app.py` compiles; `setup_python_ai.bat` and `setup_python_ai.sh` install dependencies in `.venv`; missing `sentence-transformers` returns HTTP 503 from the AI service and the Symfony moderation engine sends content to `PENDING`.

## DoctrineDoctor Runtime Steps

DoctrineDoctor v0.1.0-alpha.3 works through the Symfony dev profiler in this project.

1. Start the Symfony app in dev mode.
2. Open real forum pages: `/forum`, one post detail page, and `/admin/forum`.
3. Open the Symfony profiler toolbar for the request.
4. Inspect the DoctrineDoctor panel/data collector for N+1, slow queries, missing indexes, unsafe raw SQL, hydration and mapping warnings.
5. Fix real reported issues if the panel shows them. The current CLI verification proves the collector is registered, but the runtime panel must be inspected after browsing pages.

## Python AI Setup

Run from `Forum/web/ai_service`.

Windows:

```bat
setup_python_ai.bat
```

Linux/macOS:

```bash
sh setup_python_ai.sh
```

Start the sidecar:

```bash
uvicorn app:app --host 127.0.0.1 --port 8008
```

The Symfony forum does not install Python packages during normal runtime. If the sidecar or model is unavailable, moderation falls back to manual review with status `PENDING`.

## Remaining Environment Note

`composer self-update` tried to upgrade Composer from 2.8.12 to 2.9.7, but Windows refused write access to `C:/ProgramData/ComposerSetup/bin/composer.phar`. This is not a project dependency issue. Fix it from an Administrator PowerShell:

```powershell
composer self-update
```

If the SSL certificate error returns after Avast is re-enabled, keep TLS on and fix Windows/Avast trust instead:

1. Open Avast settings and disable HTTPS scanning only for Composer/PHP, or import Avast's web shield root certificate into Windows Trusted Root Certification Authorities.
2. Keep PHP using `C:/xampp/php/extras/ssl/cacert.pem`.
3. Recheck with:

```powershell
composer diagnose
composer config --list | Select-String -Pattern 'cafile|capath|disable-tls|secure-http'
```

Do not use `composer config --global disable-tls true`.
