# Soda Hotel

Hotel booking website built with PHP 8.1+ and Neon PostgreSQL.

## Stack

- PHP 8.1+
- PDO PostgreSQL (`pdo_pgsql`)
- Neon PostgreSQL
- HTML/CSS/Bootstrap
- PHP sessions

## Database: Neon

This project is configured for **Neon PostgreSQL**, not MySQL.

1. Create a project on Neon.
2. Open **SQL Editor**.
3. Run `database/schema.sql`.
4. In Neon, copy the **Pooled connection** string from the connection details panel.
5. Put it in `.env` as `DATABASE_URL` locally, or configure it as a secret/environment variable on your hosting platform.

Example:

```env
DATABASE_URL=postgresql://USER:PASSWORD@HOST/DBNAME?sslmode=require
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

Do **not** commit `.env` or expose `DATABASE_URL` in frontend code.

## Local setup

Requirements:

- PHP 8.1+
- PHP extension `pdo_pgsql`
- Neon account/database

```bash
copy .env.example .env
php -S localhost:8000
```

Open `http://localhost:8000/main.php`.

The application has a small built-in `.env` loader for local development, so no Composer package is required for environment loading.

## Production deployment

Set the following environment variables in your hosting provider:

```env
DATABASE_URL=postgresql://...
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

Make sure PHP has `pdo_pgsql` enabled. Configure the document root to the project directory and enable HTTPS.

## Git

`.env` is ignored by Git. A clean first commit can be created with:

```bash
git init
git add .
git commit -m "Prepare Soda Hotel for production"
git branch -M main
git remote add origin YOUR_GITHUB_REPOSITORY
git push -u origin main
```

## Security

- Passwords use `password_hash()` / `password_verify()`.
- Authentication queries use prepared statements.
- Session ID is regenerated after login.
- CSRF protection is used for payment confirmation.
- Database credentials are environment variables.
- User-visible output is escaped where appropriate.
- Booking uses a database transaction and row locking to prevent overselling the same room inventory.

## Important

The current payment flow records the selected payment method and booking status. It does **not** process real card/bank payments. A real payment gateway such as Stripe, Omise/Opn, or another supported provider must be integrated before accepting real money.

Existing legacy databases with plaintext passwords must be migrated/reset before using the new password hashing flow.

## Admin

Register an account normally, then promote it in Neon SQL Editor:

```sql
UPDATE users SET status = 'admin' WHERE username = 'YOUR_USERNAME';
```

## Neon connection note

Neon is PostgreSQL. The original application used MySQL/mysqli, so this version uses PDO PostgreSQL and the PostgreSQL schema. Do not import the old MySQL `schema.sql` into Neon.

## XAMPP / Neon SNI fix

Some Windows/XAMPP builds include an older `libpq` that cannot send TLS SNI to Neon. The old code attempted to put `options=endpoint%3D...` directly in the PDO DSN, but PDO_PGSQL does not reliably expose arbitrary URI options that way.

`config.php` now uses this flow automatically:

1. Connect normally first (preferred; keeps normal SNI/SCRAM behavior on modern clients).
2. If Neon specifically returns `Endpoint ID is not specified`, derive the endpoint ID from the hostname.
3. Retry using Neon's documented password-field fallback: `endpoint=<endpoint-id>;<password>`.

For local XAMPP, your `.env` should contain the normal Neon URL only:

```env
DATABASE_URL="postgresql://USERNAME:YOUR_NEON_PASSWORD@ep-example-123456.ap-southeast-1.aws.neon.tech/neondb?sslmode=require"
```

Do not manually add the endpoint prefix to the password; the application now handles that retry itself.

If the page says that `YOUR_NEON_PASSWORD` is still present, open Neon Console -> Connect, copy your current password/connection string, and replace the placeholder in `.env`.
