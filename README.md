# CineBook Admin

CineBook is an admin-only cinema management application on this branch. Admins
manage movies, showtimes, screens, bookings, customers, payments, and refunds.
Customer and staff-facing pages and public registration are not available.

Existing customer and staff accounts remain valid data. When they sign in, they
see only a minimal dashboard with a sign-out action. Booking, ticket, payment,
and customer records remain available to the admin workflows. The Stripe
webhook remains enabled for payment events and existing integrations.

## Local setup

Requirements: PHP 8.3+, Composer, Node.js, npm, and a configured Laravel
database. The Stripe PHP SDK also requires the PHP cURL extension.

From the project root in PowerShell:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/login`. The local seeder creates an admin account:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@cinebook.test` | `password` |

Set `SEED_PASSWORD` in `.env` to use a different local seed password. The
seeder also creates a demo customer record for admin booking data; customer
accounts do not receive customer-facing pages on this branch.

## Payments and email

Configure Stripe test credentials in `.env` before using admin refund actions
or handling Stripe webhook events. Set `STRIPE_SECRET_KEY` to a test-mode
secret key and `STRIPE_WEBHOOK_SECRET` to the signing secret for the webhook
receiver. Never commit or share secret values.

The webhook endpoint is `POST /stripe/webhook`. The admin booking tools also
send booking and cancellation emails; configure a delivery-capable mail
transport to use those actions.

## Validation

```powershell
php artisan test
npm run build
```
