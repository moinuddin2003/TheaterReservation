<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Movie reservation app

This project combines the customer booking experience with the CineBook admin
panel and staff tools. Customers can browse movies, reserve seats, and manage
their bookings; administrators manage the cinema, catalog, showtimes, payments,
and refunds; staff use the separate `/staff` screens for admissions.

Run `php artisan migrate --seed` to create the demo cinema and role accounts.
The seeded accounts use `SEED_PASSWORD` from `.env` (default: `password`):

| Role | Email | Start page |
| --- | --- | --- |
| Admin | `admin@cinebook.test` | `/admin` |
| Staff | `staff@cinebook.test` | `/staff` |
| Customer | `customer@cinebook.test` | `/dashboard` |

Use `npm run dev` while developing or `npm run build` for production assets.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## Commands For Project

composer create-project laravel/laravel theater-reservation
cd theater-reservation
composer require laravel/breeze --dev
php artisan breeze:install

## Local Stripe and Mailpit Testing (Windows)

This guide sets up a Stripe test webhook and a local email inbox. It does not
make real payments or send email to real recipients. Run commands from the
project root (`D:\Xolva\theater-reservation`) unless a step says otherwise.

### 1. Start the application

Install the project dependencies and prepare the local environment if this is a
fresh checkout:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

- `composer install` installs the PHP packages recorded in `composer.lock`,
  including this project's Stripe PHP SDK.
- `Copy-Item` creates a local `.env` file from the sample. Do not overwrite an
  existing `.env`; it contains machine-specific settings and secrets.
- `php artisan key:generate` creates the Laravel application key.

Configure the database in `.env` as required for your setup, then run:

```powershell
php artisan migrate
php artisan serve
```

- `php artisan migrate` creates the application tables in the configured
  database.
- `php artisan serve` starts Laravel at `http://127.0.0.1:8000`. Keep this
  terminal open while testing.

If the app is already installed and `php artisan serve` works, do not repeat
the fresh-checkout commands.

### 2. Create/use a Stripe test account

Create or sign in to a Stripe account at
[dashboard.stripe.com/register](https://dashboard.stripe.com/register). Use a
test environment or test mode only. Never put live credentials in a local test
setup.

This project already includes the `stripe/stripe-php` Composer SDK. The SDK is
used by PHP code to call Stripe APIs; it is **not** the Stripe command-line
program.

The app uses Stripe-hosted Checkout, so it needs a test-mode secret key to
create checkout sessions. Configure the keys in `.env` through
`config/services.php`:

```dotenv
STRIPE_PUBLISHABLE_KEY=pk_test_replace_with_your_test_publishable_key
STRIPE_SECRET_KEY=sk_test_replace_with_your_test_secret_key
STRIPE_WEBHOOK_SECRET=whsec_replace_with_the_secret_from_stripe_listen
```

Copy the test-mode publishable and secret keys from Stripe Dashboard. The
secret key must begin with `sk_test_`; this app deliberately rejects live
secret keys. The webhook signing secret is provided by the Stripe CLI below.
Keep all keys private: `.env` should not be committed or shared.

### 3. Install and log in to the Stripe CLI

Download the latest Windows ZIP from the
[Stripe CLI releases page](https://github.com/stripe/stripe-cli/releases/latest)
and extract it. Open PowerShell in the extracted folder containing
`stripe.exe`. For example:

```powershell
Set-Location "$env:USERPROFILE\Downloads\stripe_1.53.1_windows_x86_64"
.\stripe.exe --version
.\stripe.exe login
```

- `Set-Location` changes to the extracted folder. Use the actual folder name
  and path on your computer; release version numbers change.
- `.\stripe.exe --version` confirms the CLI runs.
- `.\stripe.exe login` opens a browser. Sign in and approve the CLI access.

Using `.\stripe.exe` works without adding the folder to Windows `PATH`. If
Stripe says CLI access is disabled, an account administrator may need to enable
it in Stripe Dashboard under **Settings → Team and security → MCP and CLI
access**.

### 4. Install and start Mailpit

Download the Windows binary ZIP from the
[Mailpit releases page](https://github.com/axllent/mailpit/releases/latest),
extract it, then open PowerShell in the folder containing `mailpit.exe`:

```powershell
.\mailpit.exe
```

Keep this terminal open. Mailpit receives local SMTP mail on port `1025` and
shows captured messages at [http://127.0.0.1:8025](http://127.0.0.1:8025).
It stores the messages in its local test inbox instead of delivering them to
real recipients.

In the project's `.env`, set the mail configuration:

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

After editing `.env`, open a project-root terminal and run:

```powershell
php artisan config:clear
```

This clears cached Laravel configuration so the updated `.env` values are
used.

To send a test message, start Tinker from the project root:

```powershell
php artisan tinker
```

At Tinker's `>` prompt, paste this PHP expression and press Enter:

```php
\Illuminate\Support\Facades\Mail::raw('Mailpit test email', fn ($message) => $message->to('test@example.com')->subject('Mailpit test'));
```

Then type `exit` to leave Tinker and refresh the Mailpit inbox. The message
with subject **Mailpit test** should appear. `test@example.com` is only a
captured recipient; Mailpit does not deliver it.

### 5. Forward Stripe test webhooks

The app has a `POST /stripe/webhook` route. It verifies the `Stripe-Signature`
header using `STRIPE_WEBHOOK_SECRET` and processes Checkout completion,
expiration, and refund events. Booking confirmation depends on receiving the
`checkout.session.completed` event with a paid session; forwarding only
`payment_intent.succeeded` will not confirm a booking.

Open another PowerShell window in the folder containing `stripe.exe` and
forward webhook events to the local app:

```powershell
.\stripe.exe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

Keep this terminal open. The CLI displays a webhook signing secret beginning
with `whsec_`. Copy that exact secret into `STRIPE_WEBHOOK_SECRET` in the
project `.env` (do not use a Stripe API secret beginning with `sk_test_`).
After saving `.env`, run `php artisan config:clear` from the project root.

If you changed `.env` after starting the listener, stop the listener with
`Ctrl+C` and start it again. Copy the `whsec_` secret printed by the active
listener so the app and CLI use the same secret.

Complete a booking through the app's Stripe Checkout page using a Stripe test
card, for example `4242 4242 4242 4242` with any future expiry date and any
three-digit CVC. This creates a Checkout Session tied to the booking; a generic
`stripe trigger payment_intent.succeeded` event is not a substitute because it
does not contain that booking's Checkout Session metadata.

The listener should show a successful HTTP response (2xx) after forwarding the
event. To inspect Laravel's recent log entries from the project root:

```powershell
Get-Content .\storage\logs\laravel.log -Tail 50
```

If checkout cannot be started, check for
`Stripe test-mode checkout requires a test-mode secret key` and verify
`STRIPE_SECRET_KEY` is the test-mode `sk_test_` key, not a publishable key, a
webhook secret, or a live key. If the listener shows `400`, check that
`STRIPE_WEBHOOK_SECRET` exactly matches the active listener's `whsec_` value,
then run `php artisan config:clear`. If the response is `419`, confirm
`bootstrap/app.php` excludes `stripe/webhook` from request-forgery (CSRF)
validation. After a successful payment, refresh the booking status page; it
updates from the status saved by the webhook.

Keep the Laravel server, Mailpit, and Stripe listener terminals running while
testing. The CLI trigger is for Stripe test data only. In production, configure
a public HTTPS webhook endpoint in Stripe Dashboard and use that endpoint's
signing secret; do not run `stripe listen` as the production webhook receiver.