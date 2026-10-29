# CineBook — Movie Theater Reservation System

CineBook is a Laravel application for browsing movies, reserving cinema seats, and managing theater operations. It includes customer booking flows, a staff ticket-admission interface, and an administration panel for cinema operations.

## Features

- Browse movies, genres, showtimes, and available seats.
- Reserve seats and complete bookings through Stripe Checkout.
- View bookings and tickets, including QR-based ticket scanning for staff.
- Administer movies, genres, cinemas, screens, showtimes, customers, staff, refunds, and audit records.
- Role-aware customer, staff, and administrator experiences.
- Prevent overlapping showtimes and manage temporary seat holds.

## Technology

- PHP 8.3+
- Laravel 13
- SQLite or MySQL
- Node.js and npm for frontend assets
- Stripe for hosted checkout and payment webhooks

## Getting started

### Prerequisites

Install PHP 8.3 or later with the extensions required by Laravel, Composer, Node.js, and npm. For a MySQL setup, also enable PHP's MySQL PDO extension.

### Install dependencies

Run these commands from the project root. The PowerShell commands below are suitable for Windows; use the equivalent copy and file-creation commands for your shell on macOS or Linux.

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite | Out-Null }
php artisan key:generate
npm ci
```

The sample environment uses SQLite. To use MySQL instead, configure the `DB_*` values in `.env` and create the database before migrating.

### Configure the environment and database

Review `.env` and set the database connection, mail settings, and other local values as needed. For the default SQLite setup, run:

```powershell
php artisan migrate --seed
```

The seeders create sample cinema, catalog, showtime, booking, and role-account data. They are intended for local development and demos—not production. The sample accounts use `SEED_PASSWORD` from `.env`, or `password` if it is not set:

| Role | Email | Start page |
| --- | --- | --- |
| Administrator | `admin@cinebook.test` | `/admin` |
| Staff | `staff@cinebook.test` | `/staff` |
| Customer | `customer@cinebook.test` | `/dashboard` |

Change the sample password before use in a shared environment. Do not seed these demo accounts into a production database.

### Build frontend assets and run the app

For frontend development, start Vite in one terminal:

```powershell
npm run dev
```

Start Laravel in another terminal:

```powershell
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). To create production-ready frontend assets, use:

```powershell
npm run build
```

Mail and notifications configured to use queued delivery require a queue worker. For local development, start one in a separate terminal:

```powershell
php artisan queue:work
```

## Stripe test payments

Checkout requires a Stripe test-mode secret key. Add your Stripe test credentials to `.env`:

```dotenv
STRIPE_SECRET_KEY=sk_test_replace_with_your_test_secret_key
STRIPE_WEBHOOK_SECRET=whsec_replace_with_your_webhook_signing_secret
```

Never commit `.env` or share secret keys. This project uses Stripe-hosted Checkout; the Stripe PHP SDK is already included.

**Payment integration status:** the current Stripe gateway explicitly accepts only `sk_test_` keys. Real payments and live Stripe refunds are not supported by this version. Do not configure live Stripe keys or use this application to collect real payments without first implementing and reviewing live-mode support.

To receive local webhooks, install the [Stripe CLI](https://docs.stripe.com/stripe-cli) and sign in:

```powershell
stripe login
stripe listen --events checkout.session.completed,checkout.session.expired,checkout.session.async_payment_succeeded,checkout.session.async_payment_failed,payment_intent.succeeded,refund.updated,refund.failed --forward-to http://127.0.0.1:8000/stripe/webhook
```

Copy the `whsec_...` secret printed by the active listener into `STRIPE_WEBHOOK_SECRET`. If you change `.env`, refresh Laravel's cached configuration:

```powershell
php artisan config:clear
```

Keep the Laravel server and Stripe listener running while testing. Complete a booking through the app's Checkout flow to generate events associated with that booking. Stripe's sample test card is `4242 4242 4242 4242`; use any future expiration date and any three-digit CVC. A generic CLI-triggered event is not a substitute for a checkout tied to a booking.

In production, configure a public HTTPS webhook endpoint in the Stripe Dashboard and use that endpoint's signing secret. Do not use `stripe listen` as a production webhook receiver.

## Email configuration

Mailpit is optional and is only useful for local testing when you want to capture email without delivering it. In normal use, configure `.env` with your SMTP provider's host, port, credentials, encryption, and sender address. Laravel sends mail through that provider; Mailpit is not required.

To capture local messages with [Mailpit](https://mailpit.axllent.org/) instead of sending them to real recipients, start Mailpit and use:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Open the Mailpit inbox at [http://127.0.0.1:8025](http://127.0.0.1:8025). Clear cached configuration after changing `.env`:

```powershell
php artisan config:clear
```

## Tests

Run the test suite with:

```powershell
php artisan test --compact
```

## Production deployment checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` to the HTTPS site URL.
- Configure a production database and mail provider.
- This version only supports Stripe test-mode keys. Do not accept real payments until live-mode payment and refund support has been implemented and reviewed.
- Create a secure administrator account; do not use the seeded demo accounts.
- Point the web server document root to the application's `public` directory.
- Install PHP dependencies and build frontend assets:

  ```sh
  composer install --no-dev --optimize-autoloader
  npm ci
  npm run build
  ```

- Run database migrations without reseeding demo data:

  ```sh
  php artisan migrate --force
  ```

- Create the public storage link if the app needs locally stored public files:

  ```sh
  php artisan storage:link
  ```

- Ensure `storage` and `bootstrap/cache` are writable by the web process.
- Run a persistent queue worker for queued mail and other background jobs. Restart workers after deploying code:

  ```sh
  php artisan queue:restart
  ```
- Register `https://your-domain.example/stripe/webhook` in Stripe and configure the endpoint's signing secret in `STRIPE_WEBHOOK_SECRET`.
- Keep `.env`, credentials, and production data out of source control. Back up the database and uploaded files.

php artisan serve --host=127.0.0.1 --port=8000

"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000

## Project structure

| Directory | Purpose |
| --- | --- |
| `app/Actions` | Booking, payment, ticket, and refund operations |
| `app/Http` | Controllers, middleware, and form requests |
| `app/Models` | Eloquent models |
| `app/Services` | Application services and integrations |
| `database/migrations` | Database schema |
| `database/seeders` | Development and demo data |
| `resources` | Blade views and frontend source assets |
| `routes` | Web, authentication, staff, and admin routes |
| `tests` | Automated tests |
