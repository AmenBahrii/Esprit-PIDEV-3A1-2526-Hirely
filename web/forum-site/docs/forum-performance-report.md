# Forum Performance Report

## Goal

Show measurable evidence for the S3 forum work:

- Forum feed pagination and search.
- Post detail page with comments.
- Admin moderation dashboard.
- Anti-spam and AI moderation fallback paths.
- Python AI service dependency/fallback behavior.
- Static analysis and unit tests as repeatable quality gates.
- DoctrineDoctor before/after optimization evidence.

## Environment

- Symfony 6.4 forum app.
- PHP 8.1.25 from XAMPP.
- MySQL database: `hirely` on `127.0.0.1:3306`.
- Local AI service configured at `PY_AI_URL=http://127.0.0.1:8008`.
- Date measured: 2026-05-07.

## Current Measured Commands

Run from `C:\Users\Firas\OneDrive\Desktop\hirely2\hirely\web\forum-site`.

| Check | Command | Latest result |
|---|---|---:|
| Container wiring | `Measure-Command { php bin\console lint:container }` | 982.10 ms, pass |
| Custom static checks | `Measure-Command { php tools\forum_static_checks.php }` | 4583.92 ms, pass |
| Unit tests | `Measure-Command { php tests\run_forum_unit_tests.php }` | 98.13 ms, pass |
| PHPStan | `Measure-Command { vendor\bin\phpstan analyse -c phpstan.neon.dist }` | 946.14 ms, pass |
| Fixtures dry run | `Measure-Command { php bin\console doctrine:fixtures:load --dry-run --append --no-interaction }` | 1861.67 ms, pass |
| Composer audit | `Measure-Command { composer audit }` | 2844.49 ms, pass |
| Python AI syntax | `python -m py_compile ..\ai_service\app.py` | pass |

## Current Test Data Volume

Measured with Doctrine SQL commands after MySQL was started.

| Table / metric | Value |
|---|---:|
| `forum_post` rows | 30 |
| `forum_comment` rows | 29 |
| `forum_interaction` rows | 16 |
| `forum_notification` rows | 52 |
| Approved posts | 10 |
| Pending posts | 20 |

Commands:

```powershell
php bin\console doctrine:query:sql "SELECT COUNT(*) AS posts FROM forum_post"
php bin\console doctrine:query:sql "SELECT COUNT(*) AS comments FROM forum_comment"
php bin\console doctrine:query:sql "SELECT COUNT(*) AS interactions FROM forum_interaction"
php bin\console doctrine:query:sql "SELECT COUNT(*) AS notifications FROM forum_notification"
php bin\console doctrine:query:sql "SELECT status, COUNT(*) AS total FROM forum_post GROUP BY status"
```

## Before / After

Before the S3 pass:

- Manual bundle folders existed outside Composer.
- No installed PHPStan command.
- No DoctrineFixturesBundle command.
- No DoctrineDoctor profiler integration.
- No repeatable project audit proof.
- Gemini was older missed work and only needed a brief verification after MySQL was available.

After the S3 pass:

- Manual bundle folders are removed and not restored.
- KnpPaginator, AntiSpam, DoctrineFixturesBundle, PHPStan and DoctrineDoctor are Composer-installed.
- Project audit is clean after Symfony patch updates.
- PHPStan passes with no baseline or broad ignore rules.
- 8 custom static checks and 8 unit tests pass.
- Fixture dry-run passes with `--append`.
- DoctrineDoctor is active in the Symfony web profiler.
- Gemini trigger detection and persistence were verified as an old feature, not as the main S3 focus.
- Python AI setup is explicit and portable; missing dependencies return an unavailable response and Symfony moderation marks content `PENDING`.

## DoctrineDoctor Before / After Optimization

DoctrineDoctor BEFORE result recorded on `/forum` before the repository pagination fix:

| Metric | Before |
|---|---:|
| Total issues | 48 |
| Critical issues | 8 |
| Warnings | 8 |
| Queries analyzed | 9 |

Main DoctrineDoctor problems before the fix:

- `setMaxResults()` with collection join detected.
- Unrestricted `findAll()` or `SELECT` without `LIMIT`.
- Inefficient `find()` queries where `getReference()` may be better.
- `ORDER BY` without `LIMIT` on forum post ordering: `is_pinned DESC, created_at DESC`.

Safe fixes applied:

- `/forum` now paginates the Doctrine `QueryBuilder` directly instead of loading all posts into an array and paginating in PHP.
- `/admin/forum` now paginates the Doctrine `QueryBuilder` directly instead of loading all moderation posts first.
- Admin comments and post-detail comments now paginate Doctrine queries directly.
- Like/comment metrics are hydrated only for the current page of results.
- The tag list is limited to 50 distinct tags because it is only used as a filter menu.
- Profile post history is capped to a safe recent list.
- Sorting by likes/top comments uses a hidden count subquery, so the visible behavior stays available without loading every row first.
- Opening a notification now uses a recipient-scoped query instead of a broad `find($id)` followed by a PHP ownership check.

After-code verification:

```powershell
php bin\console doctrine:query:dql "SELECT p, a, (SELECT COUNT(postLike.id) FROM App\Entity\ForumInteraction postLike WHERE postLike.targetType = 'POST' AND postLike.interactionType = 'LIKE' AND postLike.targetId = p.id) AS HIDDEN postLikeTotal FROM App\Entity\ForumPost p LEFT JOIN p.author a WHERE p.status = 'APPROVED' ORDER BY p.isPinned DESC, postLikeTotal DESC, p.createdAt DESC" --max-result=2
php bin\console doctrine:query:dql "SELECT c, a, (SELECT COUNT(commentLike.id) FROM App\Entity\ForumInteraction commentLike WHERE commentLike.targetType = 'COMMENT' AND commentLike.interactionType = 'LIKE' AND commentLike.targetId = c.id) AS HIDDEN commentLikeTotal FROM App\Entity\ForumComment c LEFT JOIN c.author a ORDER BY c.isPinned DESC, commentLikeTotal DESC, c.createdAt DESC" --max-result=2
```

After browser measurement to record in the profiler:

DoctrineDoctor v0.1.0-alpha.3 exposes the issue counts inside the Symfony browser profiler. The code-side verification passed from the terminal, but the exact after counts must be copied from the profiler panel after logging in and opening the pages.

| Page | Total issues | Critical | Warnings | Queries analyzed | Response time | Memory |
|---|---:|---:|---:|---:|---:|---:|
| `/forum` | To record after browser re-test | To record | To record | To record | To record | To record |
| `/admin/forum` | To record after browser re-test | To record | To record | To record | To record | To record |
| `/forum/post/{id}` | To record after browser re-test | To record | To record | To record | To record | To record |

## Forum Performance Decisions

- `/forum` feed paginates the Doctrine query instead of loading all posts.
- Post detail comments paginate the Doctrine query separately from posts.
- Admin moderation lists paginate Doctrine queries.
- Repository methods compute like/comment counts with grouped SQL instead of per-card queries.
- Notification creation uses a 24-hour cooldown for repeated like notifications.
- Safe Browsing skips the external API when text contains no URL.
- Gemini has fallback text for quota/rate-limit/API failures, but this is secondary evidence only.
- Python AI dependencies are not auto-installed during app runtime; setup scripts install them into `.venv`.

## DoctrineDoctor Runtime Check

DoctrineDoctor version `0.1.0-alpha.3` is a Symfony profiler integration in this project, not a standalone CLI report command.

Verification commands:

```powershell
php bin\console debug:config doctrine_doctor
php bin\console debug:container --tag=data_collector
php bin\console debug:container | findstr /i doctor
php bin\console debug:router | findstr /i doctor
php bin\console list | findstr /i doctor
```

How to inspect it during the demo:

1. Start the app in dev mode.
2. Browse forum pages such as `/forum`, a post detail page, and `/admin/forum`.
3. Open the Symfony web profiler toolbar.
4. Inspect the DoctrineDoctor panel for slow queries, N+1 warnings, missing-index warnings and unsafe-query warnings.

The router and command checks show no DoctrineDoctor route or CLI command for the installed version; the data collector/profiler is the correct verification path.
