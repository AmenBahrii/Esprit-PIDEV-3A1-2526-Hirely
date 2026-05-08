# Hirely Forum Website

Symfony 6.4 website version of the Hirely forum, built on top of the same MySQL database used by the desktop app.

## What is included

- DB-first entity generation using the teacher reverse-engineering workflow
- Login against the existing `users` table
- Home page (post-login hub)
- User forum feed
- Post CRUD
- Comment CRUD
- Admin forum dashboard and moderation CRUD
- Validation on posts and comments
- UI styled to stay visually close to the JavaFX forum

## Important folders

- `tools/teacher/` copied teacher command files from the teacher pack
- `docs/reverse-engineer/raw-entities/` raw generated entity output kept for audit
- `src/Entity/` cleaned forum-focused entities used by the website
- `src/Controller/` forum and admin web controllers
- `src/Form/` Symfony forms
- `templates/` Twig views
- `public/styles/forum.css` forum styling

## Local run

```powershell
cd web\forum-site
php bin\console about
php bin\console doctrine:mapping:info
php scripts\reverse-engineer-hirely.php
php -S 127.0.0.1:8000 -t public
```

Make sure your local MySQL server is running before executing DB commands.

Open:

- `http://127.0.0.1:8000/login`
- `http://127.0.0.1:8000/home`

## Database

Configured for the existing local Hirely database:

- host: `127.0.0.1`
- port: `3306`
- db: `hirely`
- user: `root`
- password: empty

No migrations are run automatically against the shared database.

## Teacher workflow note

The teacher reverse-engineering workflow was used first against the existing `hirely` database. The raw generated entities were preserved under `docs/reverse-engineer/raw-entities/`, then the forum-relevant entities were cleaned and rebuilt in `src/Entity/`.
