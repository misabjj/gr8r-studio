# Gr8r Studio

Gr8r Studio is a calm project-management workspace built with Laravel 13, Inertia 3, Svelte 5, and strict TypeScript. Laravel owns routing, validation, and persistence; Svelte pages render the workspace through Inertia.

## Requirements

- PHP 8.5+
- Composer
- Bun
- PostgreSQL 18+
- Redis 8

## Local setup

1. Install PHP and Bun, then install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env` and adjust credentials if needed.
3. Start PostgreSQL and Redis with `docker compose up -d`.
4. Run `php artisan key:generate` and `php artisan migrate --seed`.
5. Install frontend dependencies with `bun install`.
6. Run `php artisan serve` and `bun run dev` in separate terminals.

PostgreSQL is the application database. Redis stores sessions, cache, and queued work. Automated tests use an isolated in-memory SQLite database.

The demo seeder creates a sign-in account: `hello@gr8rstudio.com` with password `password`.

## Quality checks

```sh
php artisan test
vendor/bin/pint --test
bun run check
bun run lint
bun run build
```

Wayfinder generates typed TypeScript route helpers from Laravel routes during Vite builds. Task actions use Inertia's `useHttp` for JSON requests and update the current view without an Inertia page visit.
