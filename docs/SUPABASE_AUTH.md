# Supabase Authentication Implementation

This document explains the current authentication flow integrated with Supabase Postgres, including architecture, setup, request behavior, and security notes.

## 1. Overview

Authentication is implemented in PHP with:

- Supabase-managed PostgreSQL
- Password hashing via `password_hash(..., PASSWORD_DEFAULT)`
- Password verification via `password_verify(...)`
- Session storage via `$_SESSION['user']`
- Environment-based database credentials loaded from `.env`

Current backend is PDO-based (`pdo_pgsql`), not `pg_*` procedural API.

## 2. File Architecture

```text
backend/
├── Database.php                     # PDO connection + phpdotenv loader
├── AuthManager.php                  # Registration/login domain logic
├── register.php                     # POST endpoint for registration form
├── login.php                        # POST endpoint for login form
├── supabase_auth_schema.sql         # users table schema
└── supabase_content_schema_and_seed.sql
.env                                 # credentials (git-ignored)
composer.json                        # dependencies
vendor/                              # composer dependencies
```

## 3. Database Connection (`backend/Database.php`)

### 3.1 Runtime flow

1. Load `.env` with `vlucas/phpdotenv`.
2. Ensure PDO PostgreSQL driver exists.
3. Read env vars:
   - `DATABASE_HOST` (required)
   - `DATABASE_PORT` (optional, default `5432`)
   - `DATABASE_NAME` (optional, default `postgres`)
   - `DATABASE_USER` (required)
   - `DATABASE_PASSWORD` (required)
   - `SUPABASE_DB_SSLMODE` (optional, default `require`)
4. Build PDO DSN.
5. Create PDO connection with strict settings.

### 3.2 PDO options used

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`

### 3.3 Typical exceptions

- Missing env var: `Missing required environment variable: ...`
- Missing driver: `PDO PostgreSQL driver is not enabled in PHP...`
- Connection failure: `Failed to connect to Supabase Postgres: ...`

## 4. Auth Domain Logic (`backend/AuthManager.php`)

## 4.1 `registerUser(...)`

Input:

- `fullName`
- `username`
- `emailLocalPart`
- `major`
- `password`

Flow:

1. Normalize input (`trim`, lowercase username/email, uppercase major).
2. Build email as `<local_part>@insat.ucar.tn`.
3. Check duplicates by username or email.
4. Hash password with `password_hash`.
5. Insert into `public.users`.
6. Return user data without password hash.

Duplicate conflicts are handled through Postgres constraint code `23505`.

## 4.2 `loginUser(...)`

Input:

- `identifier` (username or full email)
- `password`

Flow:

1. Normalize identifier (trim + lowercase).
2. Query by `username` OR `email`.
3. Verify password against `password_hash`.
4. Remove `password_hash` from returned payload.

Failure returns: `Invalid username/email or password.`

## 5. HTTP Endpoints

## 5.1 Registration (`backend/register.php`)

- Method: `POST` only (`405` otherwise).
- Content type: form submission (`application/x-www-form-urlencoded`).
- Validates:
  - full name required
  - username regex (`^[a-zA-Z0-9_.-]{3,50}$`)
  - email local part format
  - major required
  - password required and min length 8
  - password confirmation
  - terms acceptance
- On success:
  - create user through `AuthManager`
  - `session_start()` and set `$_SESSION['user']`
  - return HTML success block
- On failure:
  - validation errors in HTML (`200`)
  - database/domain failure in HTML (`400`)

## 5.2 Login (`backend/login.php`)

- Method: `POST` only (`405` otherwise).
- Fields:
  - `username` (username or full email)
  - `password`
- On success:
  - authenticate through `AuthManager`
  - `session_start()` and set `$_SESSION['user']`
  - redirect to `../pages/index.html`
- On failure:
  - validation errors in HTML (`200`)
  - auth failure in HTML (`401`)

## 6. Database Schema

Defined in `backend/supabase_auth_schema.sql`:

```sql
CREATE TABLE IF NOT EXISTS public.users (
    id BIGSERIAL PRIMARY KEY,
    full_name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    major TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    role TEXT NOT NULL DEFAULT 'student'
);
```

Indexes:

- `idx_users_username`
- `idx_users_email`

## 7. Setup Instructions

### 7.1 Prerequisites

- PHP 8.1+
- `pdo_pgsql` extension enabled
- Composer installed
- Supabase project

### 7.2 Install dependencies

```bash
cd /home/dragoula/Documents/projects/Web-Dev-Project
composer install
```

### 7.3 Configure environment

Create `.env` in project root:

```env
DATABASE_HOST=aws-0-region.pooler.supabase.com
DATABASE_PORT=5432
DATABASE_NAME=postgres
DATABASE_USER=postgres.your_project_ref
DATABASE_PASSWORD=your_secure_password
SUPABASE_DB_SSLMODE=require
```

### 7.4 Create schema

Run in Supabase SQL Editor:

- `backend/supabase_auth_schema.sql`
- optionally `backend/supabase_content_schema_and_seed.sql` for clubs/events

### 7.5 Verify connection quickly

```bash
php -r "require 'vendor/autoload.php'; require 'backend/Database.php'; Database::connect(); echo 'Connected!';"
```

## 8. Security Notes

- Passwords are hashed and never stored in plaintext.
- SQL is parameterized with PDO prepared statements.
- Environment secrets are externalized in `.env`.
- DB connection uses SSL mode (`require` by default).
- Session cookie hardening should be enabled in production (`httponly`, `secure`, `samesite`).

## 9. Troubleshooting

### Error: PDO PostgreSQL driver is not enabled

Check installed modules:

```bash
php -m | grep -E 'pdo|pgsql'
```

Install/enable `pdo_pgsql` for your PHP distribution.

### Error: Missing required environment variable

Verify `.env` exists and variable names match current code (`DATABASE_*`).

### Error: Failed to load environment variables

Install dependencies:

```bash
composer install
```

### Error: Failed to connect to Supabase Postgres

Check host/user/password/port/db name, then re-test with corrected credentials.
