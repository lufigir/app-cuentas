# app-cuentas

Banking application with a microservices architecture. Everything lives in a
single repository (monorepo) for simplicity.

- **`gateway/`** — Laravel API gateway. Handles user registration, login and
  logout with Sanctum tokens, and forwards every account operation to the
  accounts microservice.
- **`microservicio_cuentas/`** — Flask microservice. Owns the `accounts` table
  and exposes the CRUD over MySQL.

```
client ──► gateway (Laravel :8000) ──► microservicio_cuentas (Flask :5000)
              gateway_db                      microservicio_cuentas
```

Each service owns its own database: `gateway_db` for users and tokens,
`microservicio_cuentas` for accounts.

## Requirements

- PHP 8.2+ and Composer
- Python 3.11+
- MySQL 8 (Laragon works out of the box)

## Setup

### Accounts microservice (Flask)

```sh
cd microservicio_cuentas
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env_example .env      # set DB_NAME and your MySQL credentials
set FLASK_APP=app
flask db upgrade
python run.py               # http://localhost:5000
```

### Gateway (Laravel)

```sh
cd gateway
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8000   # http://localhost:8000
```

## API

### Users (gateway)

| Method | Endpoint | Body |
| --- | --- | --- |
| POST | `/api/register` | `name`, `document`, `email`, `rol`, `password` |
| POST | `/api/login` | `email`, `password` |
| POST | `/api/logout` | — (requires the token) |

`login` answers `{ "token": "...", "response": "Usuario autenticado correctamente" }`.
Send that token as `Authorization: Bearer <token>` on every account request.

### Accounts (gateway → Flask)

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
  "document": "1001",
  "email": "ada@example.com",
  "account_number": "ACC-0001"
}
```

`document`, `email` and `account_number` are unique. The gateway returns the
microservice answer untouched, so a duplicate surfaces as `409` and a missing
account as `404`.

## Layout

```
gateway/                          Laravel API gateway
  app/Http/Controllers/UserController.php      register / login / logout
  app/Http/Controllers/AccountController.php   proxy to the microservice
  routes/api.php
microservicio_cuentas/            Flask microservice
  app.py                          application factory, routes, error handlers
  config.py                       environment-driven configuration
  extensions.py                   SQLAlchemy and Migrate instances
  errors.py                       AppError hierarchy mapped to HTTP codes
  models/account.py               Account model
  services/account_service.py     business logic and validation
  migrations/                     Alembic migrations
sql/schema.sql                    raw DDL
```
