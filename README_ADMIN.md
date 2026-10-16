# CineBook Admin Backend

The admin panel reads and writes the existing MySQL schema with Eloquent. It has no mock-data repositories or per-module data-source switch. Admin pages use real database records; demo records are inserted only by the database seeder.

## Environment

Configure the database connection in `.env`, then set the administrator credentials:

```dotenv
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=replace-with-a-strong-password
```

Stripe operations are restricted to test mode. Configure `STRIPE_SECRET` with an `sk_test_` key and `STRIPE_WEBHOOK_SECRET` before testing checkout or webhook flows. Never use live Stripe credentials for these admin test workflows.

## Setup

Run these commands from the Laravel application directory:

```shell
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`DatabaseSeeder` creates or updates the admin account and settings, then runs `DemoDataSeeder`. Demo data is skipped in production and whenever movies already exist; it is generated in one transaction. To receive queued mail and refund jobs locally, run `php artisan queue:work`. To expire seat holds on schedule, run `php artisan schedule:work`.

## Schema limitations

The current `users` table has no active/deactivated column, so account deactivation is not exposed. The `stripe_events` table has no payload column; replay retrieves the event from Stripe in test mode rather than persisting a payload. These limitations are handled without schema changes.
