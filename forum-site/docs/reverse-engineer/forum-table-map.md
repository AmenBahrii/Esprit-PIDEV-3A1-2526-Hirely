# Forum Table Audit

## Reverse-engineering foundation used

- Primary bootstrap: teacher standalone script workflow (`scripts/reverse-engineer-hirely.php`) adapted from provided `reverse-engineer.php`
- Generation target: `docs/reverse-engineer/raw-entities/` (audit only, no overwrite of cleaned production entities)
- Secondary option kept: teacher Symfony command files in `tools/teacher/`

Teacher reverse-engineering was run against the existing `hirely` database first, then the generated output was audited and reduced to the forum scope.

| Table | Purpose | Keep? | Reason |
| --- | --- | --- | --- |
| `users` | Forum ownership, login, role lookup | Keep | Required for authentication and post/comment authorship |
| `role` | User role names like `Admin` and `candidate` | Keep | Required for admin vs user separation |
| `forum_post` | Forum posts | Keep | Core feature |
| `forum_comment` | Forum comments | Keep | Core feature |
| `forum_interaction` | Post likes | Keep | Needed for like counts/toggle in the web forum |
| `forum_notification` | Desktop/forum notifications | Ignore for now | Not required for this week’s website CRUD scope |
| `application` | Applications module | Ignore | Outside forum scope |
| `joboffer` | Job offers module | Ignore | Outside forum scope |
| `onboardingplan` | Onboarding module | Ignore | Outside forum scope |
| `onboardingtask` | Onboarding module | Ignore | Outside forum scope |
| `password_reset_otp` | Desktop password reset | Ignore for now | Website task only needs forum login |
| `evaluation_criteria` | Interview feature | Ignore | Outside forum scope |
| `interviews` | Interview feature | Ignore | Outside forum scope |
| `interview_evaluations` | Interview feature | Ignore | Outside forum scope |
| `interview_types` | Interview feature | Ignore | Outside forum scope |
| `roles` | Extra table not used by `users.role_id` | Ignore | Live forum/auth flow references `role`, not `roles` |

Notes:

- Raw teacher-generated entities are preserved in `docs/reverse-engineer/raw-entities/`.
- Clean forum-focused entities were rebuilt in `src/Entity/` after auditing the generated output.
