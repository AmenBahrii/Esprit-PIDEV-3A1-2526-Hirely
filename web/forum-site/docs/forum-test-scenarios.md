# Forum Test Scenarios And Data

## Fixture Accounts

`src/DataFixtures/ForumFixtures.php` creates a small demo set when loaded in a dev/test database.

| Account | Role | Password | Purpose |
|---|---|---|---|
| `forum.alice@hirely.local` | user | `password` | Normal post author |
| `forum.bob@hirely.local` | user | `password` | Commenter/recruiter |
| `forum.admin@hirely.local` | admin | `password` | Moderation demo |

The fixture also creates:

- 3 posts: approved/pinned, pending, rejected/locked.
- 2 comments, including one old `@gemini` verification example.
- 2 likes.
- 2 notifications.
- Real moderation notes with duplicate, toxicity, relevance, quality and link-threat metrics.

Safe fixture commands:

```powershell
php bin\console doctrine:fixtures:load --dry-run --append --no-interaction
php bin\console doctrine:fixtures:load --append --no-interaction
php bin\console doctrine:fixtures:load --env=dev
```

Use `--append` for an existing dev database. Only run without `--append` on a disposable dev/test database because the default command purges existing data. Never run fixtures on production data.

## Scenario 1 - User Creates A Valid Post

Data:

- Title: `Symfony internship interview tips`
- Tag: `#Career`
- Content: `I have a Symfony internship interview next week. What should I revise first?`

Expected:

- Form validation passes.
- AntiSpam accepts the submission.
- Moderation sets status to `APPROVED` or `PENDING`.
- Post appears in the owner view. It appears in the public feed only if approved.

## Scenario 2 - Anti-Spam Blocks Suspicious Content

Data:

- Title: `Fast income offer`
- Tag: `#General`
- Content: `earn money fast contact me on telegram http://example.com http://example.com`

Expected:

- AntiSpam or moderation blocks/reviews the content.
- User receives an error or pending/rejected moderation result.
- Admin can inspect the moderation note.

## Scenario 3 - Brief Gemini Verification

Data:

- Comment on an approved, unlocked post: `@gemini give me a short checklist for this topic`

Expected:

- Trigger detection sees `@gemini`.
- `gemini@hirely.local` is used as system author.
- Gemini reply is saved as an approved comment.
- If quota is exceeded, the saved reply clearly says quota/rate limit reached.
- This is a brief old-feature smoke test, not the main Grille S3 focus.

## Scenario 4 - Like And Notification

Data:

- User A likes User B's approved post.

Expected:

- `forum_interaction` creates one `POST` + `LIKE` row.
- Clicking again removes the like.
- User B receives one notification.
- Repeated likes from the same actor are cooled down for 24 hours.

## Scenario 5 - Comment Moderation

Data:

- Clean comment: `This is helpful, thanks for the explanation.`
- Suspicious comment with many links or toxic language.

Expected:

- Clean comment can be approved automatically.
- Suspicious comment becomes `PENDING` or `REJECTED`.
- Admin can edit the moderation status.

## Scenario 6 - Admin Moderation Dashboard

Steps:

1. Login as `forum.admin@hirely.local`.
2. Open `/admin/forum`.
3. Filter by `PENDING`.
4. Open a post.
5. Run AI analysis or reclassify.
6. Approve or reject content.

Expected:

- Admin sees all statuses.
- Status changes create notifications for authors.
- AI feedback page shows moderation metrics.

## Scenario 7 - Pagination And Search

Steps:

1. Open `/forum`.
2. Search for `Symfony`.
3. Filter by `#Career`.
4. Sort by newest, oldest and most liked.

Expected:

- Feed remains paginated.
- Results match title/content/tag/author search.
- Pinned content stays first.

## Automated Proof

```powershell
php tools\forum_static_checks.php
php tests\run_forum_unit_tests.php
vendor\bin\phpstan analyse -c phpstan.neon.dist
php bin\console lint:twig templates
python -m py_compile ..\ai_service\app.py
```

Current automated coverage includes a brief Gemini trigger smoke test, prompt cleanup, tag normalization, like normalization, notification message length, moderation metric parsing and Safe Browsing URL short-circuit behavior. The main S3 evidence remains static analysis, unit tests, DoctrineDoctor, performance reporting, scenarios and fixture data.

## Python AI Portability Scenario

Steps:

1. Run `web/ai_service/setup_python_ai.bat` on Windows, or `sh setup_python_ai.sh` on Linux/macOS.
2. Start `uvicorn app:app --host 127.0.0.1 --port 8008`.
3. Open `/health` and confirm dependencies are available.
4. If the service is stopped or `sentence-transformers` is missing, submit forum content.

Expected:

- Symfony does not crash.
- Python AI returns an unavailable response or the HTTP request fails cleanly.
- Moderation stores the content as `PENDING`.
- The moderation note explains that AI was unavailable and manual review is required.

## Team Integration Scenario - Onboarding Runtime Slice

Purpose:

Prove that the first team module from Hirely v3 is integrated without breaking the forum/S3 work.

Setup:

1. Review `docs/integration/onboarding_runtime_schema.sql`.
2. Apply it only on a dev/test database if onboarding tables are missing.
3. Login as an existing user.
4. Open `/onboarding`.
5. Open `/onboarding/plans/1` if demo data exists.

Expected:

- The current Hirely/forum layout remains visible.
- The navbar includes `Onboarding`.
- `/onboarding` opens the runtime module.
- If tables are missing, the page shows a setup message instead of crashing.
- If demo data exists, plans and tasks are listed with status, deadline, task count, blocked count and attachments.
- Forum routes such as `/forum` and `/admin/forum` still pass their existing scenarios.
