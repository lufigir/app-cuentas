# app-cuentas

A two-service system: a **Laravel API gateway** that authenticates clients and
forwards account operations to a **Flask accounts microservice** backed by MySQL.

```
client ──► gateway (Laravel :8000)  ──X-API-Key──►  accounts-service (Flask :5000)
              Sanctum token auth                        gateway_db   accounts_db
              request validation
```

The gateway is the only public entry point. The Flask service listens on
`127.0.0.1` and rejects every `/api/*` request without a valid `X-API-Key`.
Each service owns its own database: `gateway_db` (users, tokens) and
`accounts_db` (accounts).

## Requirements

- PHP 8.2+ and Composer
- Python 3.11+
- MySQL 8 (Laragon works out of the box)

## Setup

### 1. Databases

```sh
mysql -uroot < sql/schema.sql
```

`schema.sql` creates both databases and the `accounts` table. If you prefer
migrations, create the databases only and let each service migrate (see below).

### 2. Accounts service (Flask)

```sh
cd accounts-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env      # set API_KEY and your MySQL credentials
flask db upgrade            # requires FLASK_APP=app
python run.py               # http://127.0.0.1:5000
```

### 3. Gateway (Laravel)

```sh
cd gateway
composer install
copy .env.example .env
php artisan key:generate
# set ACCOUNTS_SERVICE_KEY to the same value as the service's API_KEY
php artisan migrate
php artisan serve --port=8000   # http://127.0.0.1:8000
```

## API

All account endpoints require a Sanctum bearer token.

### Auth (gateway only)

| Method | Endpoint | Body |
| --- | --- | --- |
| POST | `/api/register` | `name`, `email`, `password`, `password_confirmation` |
| POST | `/api/login` | `email`, `password` |
| POST | `/api/logout` | — (revokes the current token) |
| GET | `/api/me` | — |

Both `register` and `login` answer `{ "user": {...}, "token": "..." }`.

### Accounts (proxied to Flask)

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/accounts` | List every account |
| GET | `/api/accounts/{id}` | Read one account |
| POST | `/api/accounts` | Create an account |
| PUT | `/api/accounts/{id}` | Update an account (partial body allowed) |
| DELETE | `/api/accounts/{id}` | Delete an account (`204`) |

Account payload:

```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "email": "ada@example.com",
  "account_number": "ACC-0001",
  "balance": "150.50"
}
```

`balance` is optional on create and defaults to `0.00`. `account_number` is unique.

### Example

```sh
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"ada@example.com","password":"secret-password"}' | jq -r .token)

curl -s http://127.0.0.1:8000/api/accounts \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

### Errors

The gateway returns the microservice answer untouched — same status code, same
body — so a duplicate `account_number` surfaces as
`409 {"error": "An account with that account_number already exists"}`.
Gateway-side validation fails with `422` before any call is made, and an
unreachable microservice answers `503 {"error": "The accounts service is unavailable."}`.

## Tests

```sh
cd accounts-service && .venv\Scripts\python -m pytest    # 17 tests
cd gateway && php artisan test                           # 19 tests
```

Flask tests run against an in-memory SQLite database; the gateway tests fake the
HTTP calls to the microservice, so neither suite needs MySQL or a running service.

## Layout

```
accounts-service/      Flask microservice
  app.py               application factory, routes, error handlers, API-key guard
  config.py            environment-driven configuration
  extensions.py        SQLAlchemy and Migrate instances
  errors.py            AppError hierarchy mapped to HTTP status codes
  models/account.py    Account model
  services/            business logic and validation
  migrations/          Alembic migrations
  tests/               pytest suite
gateway/               Laravel API gateway
  app/Services/        AccountsServiceClient (HTTP client for Flask)
  app/Http/Requests/   request validation
  app/Http/Controllers/Api/
  routes/api.php
  tests/Feature/
sql/schema.sql         raw DDL for both databases
```
