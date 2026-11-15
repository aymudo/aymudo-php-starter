# Aymudo PHP Starter

A production-oriented PHP 8.2+ starter dashboard that automatically adapts to the
features assigned to an Aymudo Platform store.

## Quick start

```bash
git clone https://github.com/aymudo/aymudo-php-starter.git
cd aymudo-php-starter
cp .env.example .env
composer install
composer serve
```

Open <http://127.0.0.1:8080>.

Set these values in `.env`:

```dotenv
AYMUDO_API_KEY=apk_live_replace_me
AYMUDO_STORE_ID=str_replace_me
```

The secret remains server-side. It is never embedded in HTML or JavaScript.

## Automatic feature discovery

At startup the dashboard reads:

- `GET /capabilities` for friendly first-class modules.
- `GET /app/operations` for exact, source-reviewed operations assigned to the store.

Navigation is generated from the response. Disabled products are not rendered. When an
administrator changes a workspace assignment, the dashboard reflects it on the next request
without a code release.

The **API operations** screen is a schema-aware escape hatch for every assigned `/app`
operation. It rejects operations that are not present in the live catalogue, requires an
explicit confirmation for writes, protects forms with CSRF, caps JSON input size and sends
all writes with an idempotency key. Replace the generic JSON field with product-specific
forms as your integration matures.

## Docker

```bash
cp .env.example .env
docker compose up --build
```

Then open <http://127.0.0.1:8080>.

## Included views

Appointments, customers, supported conversations, social content, tasks, additions,
notifications, analytics and the live operation catalogue. A view appears only when the
workspace exposes its capability.

API documentation: <https://www.aymudo.com/developers>

## License

MIT
