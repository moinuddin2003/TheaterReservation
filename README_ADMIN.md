# CineBook Admin Backend

The admin panel reads and writes the existing MySQL schema with Eloquent. It has no mock-data repositories or per-module data-source switch. Admin pages use real database records; demo records are inserted only by the database seeder.

## Environment

Configure the database connection in `.env`, then set the administrator credentials:

```dotenv
ADMIN_EMAIL=your-admin@example.com
ADMIN_PASSWORD=your-strong-password
```

Stripe operations are restricted to test mode. Configure `STRIPE_SECRET` with an `sk_test_` key and `STRIPE_WEBHOOK_SECRET` before testing checkout or webhook flows. Never use live Stripe credentials for these admin test workflows.

## Setup

Run these commands from the Laravel application directory:

```shell
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan queue:work
```

`DatabaseSeeder` creates the configured admin only if no user already has `ADMIN_EMAIL`; it never changes an existing account's password or role. If either credential is unset, an existing admin is left untouched and seeding continues silently. If there is no admin, seeding prints a warning to set both `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` and continues without creating one. `DatabaseSeeder` then seeds settings and runs `DemoDataSeeder`. Demo data is skipped in production and whenever movies already exist; it is generated in one transaction. The queue worker processes queued mail and refund jobs. To expire seat holds on schedule, run `php artisan schedule:work`.

## Schema limitations

The current `users` table has no active/deactivated column, so the admin UI persists account activation overrides as audited `settings` keys (for example, `user_active_42`) and rejects logins for deactivated accounts. Stripe event rows contain no payload column; the event viewer shows the persisted event metadata and replay retrieves the event from Stripe in test mode. Currency and the 2-hour/100% customer cancellation rule are fixed by the current checkout/business logic and are displayed as read-only.
