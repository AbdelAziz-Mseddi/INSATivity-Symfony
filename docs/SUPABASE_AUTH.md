# Supabase Authentication Implementation

This document explains the authentication system integrated with Supabase Postgres, including architecture, functions, setup, and usage.

## Overview

The authentication system provides secure user registration and login using Supabase's managed PostgreSQL database. User credentials are stored in a dedicated `public.users` table with password hashing via `PASSWORD_DEFAULT` (bcrypt).

### Key Features
- **Secure password hashing** – bcrypt via PHP's `password_hash()`
- **SQL injection prevention** – parameterized queries with `pg_query_params()`
- **Session management** – tracked via `$_SESSION['user']`
- **Flexible authentication** – login by username or email
- **Environment-based config** – credentials loaded from `.env`

---

## Architecture

### File Structure

```
backend/
├── Database.php          # Database connection + phpdotenv loader
├── AuthManager.php       # User registration/login logic
├── register.php          # POST endpoint for registration
├── login.php             # POST endpoint for login
└── supabase_auth_schema.sql  # Schema creation script
.env                      # Supabase credentials (git-ignored)
.env.example              # Template for .env
composer.json             # PHP dependencies (phpdotenv)
vendor/                   # Composer dependencies (git-ignored)
```

---

## Component Documentation

### 1. Database.php

**Purpose:** Manages Supabase Postgres connection and environment variable loading via phpdotenv.

#### Dependencies
- **phpdotenv** (^5.5) – Battle-tested PHP `.env` loader (equivalent to Python's `python-dotenv`)
- Installed via Composer; auto-loaded in `vendor/autoload.php`

#### Public Methods

##### `Database::connect(): resource` via phpdotenv
2. Validates required credentials
3. Constructs secure connection string
4. Establishes SSL-required connection
5. Throws exception on failure with diagnostic message

#### Environment Variable Loader

The `loadEnvironment()` method uses **vlucas/phpdotenv** to safely parse `.env`:

```php
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
```

**Why phpdotenv:**
- Widely used in PHP production apps (Laravel, Symfony use it)
- Handles quoted values, comments, multiline strings
- Safer than custom parsing
- Works with system environment variables (doesn't override)
2. Validates required credentials
3. Constructs secure connection string
4. Establishes SSL-required connection
5. Throws exception on failure with diagnostic message

**Example:**
```php
require_once 'Database.php';
$connection = Database::connect();
```

**Error Handling:**
- Missing env vars throw: `Missing required environment variable: {KEY}`
- Missing PHP extension throws: `PostgreSQL extension is not enabled in PHP`
- Connection failure throws: `Failed to connect to Supabase Postgres`

#### Private Methods

##### `getRequiredEnv(string $key): string`
Retrieves a required environment variable or throws exception.

```php
$host = self::getRequiredEnv('SUPABASE_DB_HOST');
```

##### `escapeConnectionValue(string $value): string`
Escapes backslashes and quotes in connection string values to prevent injection.

```php
$escaped = self::escapeConnectionValue($password);
```

##### `loadEnvironment(): void`
Static method that loads `.env` into `putenv()` and `$_ENV`. Runs once per script execution.

**Features:**
- Skips comment lines (lines starting with `#`)
- Handles quoted values (`"value"` or `'value'`)
- Only sets env vars if not already set (preserves system env)
- Silent failure on missing `.env` (allows system env vars)

---

### 2. AuthManager.php

**Purpose:** Core business logic for user registration and login against the `public.users` table.

#### Constructor

```php
public function __construct()
```
Establishes database connection via `Database::connect()`.

**Example:**
```php
$auth = new AuthManager();
```

#### Public Methods

##### `registerUser(string $fullName, string $username, string $emailLocalPart, string $major, string $password): array`

Creates a new user in the database.

**Parameters:**
- `$fullName` – User's full name (required, trimmed)
- `$username` – Login identifier (required, lowercased, trimmed)
- `$emailLocalPart` – Email prefix; full email = `{part}@insat.ucar.tn` (required, lowercased)
- `$major` – Academic major (required, uppercased)
- `$password` – Plaintext password (required, hashed via bcrypt)

**Returns:** Associative array with registered user data:
```php
[
    'id'         => 1,
    'full_name'  => 'Ahmed Ben Ali',
    'username'   => 'ahmed_ben_ali',
    'email'      => 'ahmed.benali@insat.ucar.tn',
    'major'      => 'MPI',
    'created_at' => '2026-03-31T10:45:30+00:00'
]
```

**Validation:**
1. Checksdup username/email (case-insensitive)
2. Fails if username or email already exists
3. Hashes password with `password_hash($password, PASSWORD_DEFAULT)`

**Exceptions:**
- `Username or university email already exists.`
- `Failed to secure password.`
- `Failed to validate existing user account.`
- `Failed to create user account in database.`

**SQL Query:**
```sql
INSERT INTO public.users 
  (full_name, username, email, major, password_hash)
VALUES ($1, $2, $3, $4, $5)
RETURNING id, full_name, username, email, major, created_at
```

---

##### `loginUser(string $identifier, string $password): array`

Authenticates a user by username or email.

**Parameters:**
- `$identifier` – Username or full email (lowercased, trimmed)
- `$password` – Plaintext password (verified against hash)

**Returns:** Associative array with user data (sans `password_hash`):
```php
[
    'id'         => 1,
    'full_name'  => 'Ahmed Ben Ali',
    'username'   => 'ahmed_ben_ali',
    'email'      => 'ahmed.benali@insat.ucar.tn',
    'major'      => 'MPI'
]
```

**Lookup Logic:**
1. Searches `public.users` by `username` OR `email` (case-insensitive)
2. Verifies password via `password_verify($password, $hash)`
3. Returns user on match, throws exception on mismatch

**Exceptions:**
- `Invalid username/email or password.`
- `Failed to query user account.`

**SQL Query:**
```sql
SELECT id, full_name, username, email, major, password_hash
FROM public.users
WHERE username = $1 OR email = $1
LIMIT 1
```

---

### 3. register.php

**Purpose:** HTTP endpoint for user registration form submission.

#### Request

**Method:** `POST`  
**Content-Type:** `application/x-www-form-urlencoded` (HTML form submission)

**Expected POST fields:**
```
fullName=Ahmed Ben Ali
username=ahmed_ben_ali
email=ahmed.benali          (without @insat.ucar.tn)
major=MPI
password=SecurePass123
confirmPassword=SecurePass123
acceptTerms=on
```

#### Response on Success

**Status:** 200 OK  
**Output:** HTML page confirming registration with redirect link

```html
<h3 style='color:green;'>Registration successful!</h3>
<p>Welcome, Ahmed Ben Ali.</p>
<p>Account created for ahmed.benali@insat.ucar.tn.</p>
<a href='../pages/login.html'>Continue to Login</a>
```

**Session Action:** Sets `$_SESSION['user']` with user data for future requests.

#### Response on Validation Error

**Status:** 200 OK  
**Output:** HTML error list with back link

```html
<h3>Errors:</h3>
<p style='color:red;'>Username must be 3-50 chars...</p>
<a href='../pages/register.html'>Go Back</a>
```

#### Response on Database Error

**Status:** 400 Bad Request  
**Output:** HTML error message with back link

```html
<h3>Registration failed:</h3>
<p style='color:red;'>Username or university email already exists.</p>
<a href='../pages/register.html'>Go Back</a>
```

#### Validation Rules

| Field | Rule | Error |
|-------|------|-------|
| fullName | Non-empty | "Full Name is required." |
| username | 3–50 chars, alphanumeric + `_.-` | "Username must be 3-50 chars..." |
| email | Non-empty, valid chars | "Invalid university email format." |
| major | Non-empty | "Major is required." |
| password | ≥8 chars | "Password must be at least 8 characters." |
| confirmPassword | Matches password | "Passwords do not match." |
| acceptTerms | Checkbox checked | "You must accept the terms..." |

---

### 4. login.php

**Purpose:** HTTP endpoint for user login form submission.

#### Request

**Method:** `POST`  
**Content-Type:** `application/x-www-form-urlencoded` (HTML form submission)

**Expected POST fields:**
```
username=ahmed_ben_ali          (or full email: ahmed.benali@insat.ucar.tn)
password=SecurePass123
```

#### Response on Success

**Status:** 302 Found  
**Location:** `../pages/index.html`  
**Session Action:** Sets `$_SESSION['user']` with user data

#### Response on Validation Error

**Status:** 200 OK  
**Output:** HTML error list with back link

```html
<h3>Errors:</h3>
<p style='color:red;'>Username or university email is required.</p>
<a href='../pages/login.html'>Go Back</a>
```

#### Response on Auth Failure

**Status:** 401 Unauthorized  
**Output:** HTML error message with back link

```html
<h3>Login failed:</h3>
<p style='color:red;'>Invalid username/email or password.</p>
<a href='../pages/login.html'>Go Back</a>
```

#### Validation Rules

| Field | Rule | Error |
|-------|------|-------|
| username/email | Non-empty | "Username or university email is required." |
| password | Non-empty | "Password is required." |

#### Login Logic

1. Accepts username or full email as identifier
2. Calls `AuthManager::loginUser()` with trimmed, lowercased input
3. On success: creates session, redirects to homepage
4. On failure: returns 401 with error message

---

### 5. supabase_auth_schema.sql

**Purpose:** SQL schema for the `public.users` table. Run once in Supabase SQL Editor.

#### Table: `public.users`

```sql
CREATE TABLE IF NOT EXISTS public.users (
    id BIGSERIAL PRIMARY KEY,
    full_name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    major TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_username ON public.users (username);
CREATE INDEX IF NOT EXISTS idx_users_email ON public.users (email);
```

**Columns:**

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| id | BIGSERIAL | PRIMARY KEY | Auto-incrementing user ID |
| **Composer** installed (PHP dependency manager)
- Supabase project with PostgreSQL database
- `.env` file in project root (git-ignored)

### Step 0: Install Composer Dependencies

Navigate to project root and install phpdotenv:

```bash
cd /home/dragoula/Documents/projects/Web-Dev-Project
composer install
```

This creates `vendor/` directory with autoloaders.

**First time?**
```bash
# Download Composer if not installed
curl -sS https://getcomposer.org/installer | php

# Or on macOS (Homebrew)
brew install composer
```

**What gets installed:**
- `vlucas/phpdotenv` (^5.5) – loads `.env` files safely
- Composer autoloader – auto-requires phpdotenv in `Database.php`

**Version Control:**
- `.gitignore` excludes `vendor/` (regenerated per environment)
- `composer.json` and `composer.lock` are committed (ensures reproducibilityin identifier |
| email | TEXT | NOT NULL, UNIQUE | University email (full) |
| major | TEXT | NOT NULL | Academic program (MPI, CBA, etc.) |
| password_hash | TEXT | NOT NULL | bcrypt hash of password |
| created_at | TIMESTAMPTZ | DEFAULT NOW() | Registration timestamp (UTC) |

**Indexes:**
- `idx_users_username` – Fast lookup by username in `loginUser()`
- `idx_users_email` – Fast lookup by email in `loginUser()` and `registerUser()`

---

## Setup Instructions

### Prerequisites
- PHP 8.1+ with `pgsql` extension enabled
- Supabase project with PostgreSQL database
- `.env` file in project root (git-ignored)

### Step 1: Configure Environment Variables

Copy `.env.example` to `.env` and fill in Supabase credentials:

```bash
cp .env.example .env
```

Edit `.env`:
```
SUPABASE_DB_HOST=aws-0-region.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.your_project_ref
SUPABASE_DB_PASSWORD=your_secure_password
SUPABASE_DB_SSLMODE=require
```

**Where to find these:**
1. Log into Supabase dashboard
2. Select your project
3. Go to **Settings** → **Database**
4. Copy connection details from the "Connection pooler" section (recommended over direct connection for PHP)

### Step 2: Create Database Schema

1. In Supabase dashboard, go to **SQL Editor**
2. Create a new query
3. Paste contents of `backend/supabase_auth_schema.sql`
4. Click "Run"

Expected output: `Query successful` (no rows)

### Step 3: Verify Connection

Test the connection in terminal:

```bash
cd /home/dragoula/Documents/projects/Web-Dev-Project

# Make sure dependencies are installed
composer install

# Test PHP connection
php -r "require 'vendor/autoload.php'; require 'backend/Database.php'; Database::connect(); echo 'Connected!';"
```

**Success:** Outputs `Connected!`  
**Error:** Outputs exception message (mismatched credentials, missing `.env`, missing phpdotenv, no extension, etc.)

### Step 4: Start Web Server

```bash
php -S localhost:8000
```

Navigate to: `http://localhost:8000/pages/register.html`

---

## Usage Examples

### Register a User (Frontend)

```html
<form action="../backend/register.php" method="POST">
  <input type="text" name="fullName" placeholder="Full Name" required />
  <input type="text" name="username" placeholder="Username" required />
  <input type="text" name="email" placeholder="Email (without @insat.ucar.tn)" required />
  <select name="major" required>
    <option value="MPI">MPI</option>
    <option value="CBA">CBA</option>
    <!-- ... -->
  </select>
  <input type="password" name="password" placeholder="Password" required />
  <input type="password" name="confirmPassword" placeholder="Confirm Password" required />
  <label>
    <input type="checkbox" name="acceptTerms" required />
    I accept terms & privacy policy
  </label>
  <button type="submit">Create Account</button>
</form>
```

### Login a User (Frontend)

```html
<form action="../backend/login.php" method="POST">
  <input type="text" name="username" placeholder="Username or Email" required />
  <input type="password" name="password" placeholder="Password" required />
  <button type="submit">Login</button>
</form>
```

### Access User Session (Backend/Frontend)

After login, user data is available in PHP:

```php
session_start();
if (isset($_SESSION['user'])) {
    echo "Logged in as: " . $_SESSION['user']['full_name'];
    echo "Email: " . $_SESSION['user']['email'];
    echo "Major: " . $_SESSION['user']['major'];
}
```

---

## Security Considerations

### 1. Password Hashing
- Passwords are hashed via `password_hash($password, PASSWORD_DEFAULT)` (bcrypt)
- Never stored or logged in plaintext
- Verified with `password_verify()` on login

### 2. SQL Injection Prevention
- All queries use parameterized statements via `pg_query_params()`
- Parameter placeholders: `$1, $2, $3, ...`
- User input never concatenated into SQL

**Example:**
```php
pg_query_params(
    $connection,
    'SELECT * FROM public.users WHERE username = $1',
    [$username]
);
```

### 3. Data Validation
- Input trimmed and type-checked client-side and server-side
- Username regex: alphanumeric, underscore, dot, hyphen only
- Email format validated before DB insert
- Password minimum 8 characters

### 4. HTTPS (Production)
- Supabase connection uses `sslmode=require` (encryption in transit)
- For production, ensure PHP runs over HTTPS
- Sensitive env vars should use secrets manager (not `.env` in production)

### 5. Session Security
- Sessions tracked via PHP's default session handler
- Configure session cookie flags in `php.ini`:
  ```ini
  session.cookie_httponly = 1
  session.cookie_secure = 1
  session.cookie_samesite = Strict
  ```

---

## Troubleshooting

### Error: "PostgreSQL extension is not enabled"

**Solution:**
```bash
php -m | grep pgsql
```

If not listed, enable it:
- **Ubuntu/Debian:** `sudo apt-get install php-pgsql && sudo systemctl restart apache2`
- **macOS (Homebrew):** `brew install php@8.x` (includes pgsql)
- **Windows:** Uncomment `extension=pdo_pgsql` and `extension=pgsql` in `php.ini`

### Error: "Missing required environment variable: SUPABASE_DB_HOST"

**Solution:** Verify `.env` exists and is readable in project root with valid credentials.

```bash
ls -la .env
cat .env
```

If missing, copy from template:
```bash
cp .env.example .env
# Edit .env with actual credentials
```

### Error: "Failed to load environment variables: file 'vendor/autoload.php' not found"

**Solution:** Install Composer dependencies:
```bash
composer install
```

### Error: "Failed to connect to Supabase Postgres"

**Possible causes:**
- Incorrect host/port/credentials
- Network firewall blocking connection
- SSL/TLS handshake failure

**Debug:**
```bash
# Test PostgreSQL connection directly
psql -h <HOST> -U <USER> -d postgres -c "SELECT 1;"

# Verify credentials in .env
cat .env | grep SUPABASE_DB
```

### Error: "Username or university email already exists"

**Solution:** Choose a unique username or different email local part.

---

## API Response Format

### Registration Success
```json
{
  "status": "success",
  "message": "User registered and session created",
  "session": {
    "id": 1,
    "full_name": "Ahmed Ben Ali",
    "username": "ahmed_ben_ali",
    "email": "ahmed.benali@insat.ucar.tn",
    "major": "MPI"
  }
}
```

### Login Success
```json
{
  "status": "success",
  "message": "User logged in",
  "session": {
    "id": 1,
    "full_name": "Ahmed Ben Ali",
    "username": "ahmed_ben_ali",
    "email": "ahmed.benali@insat.ucar.tn",
    "major": "MPI"
  }
}
```

### Error
```json
{
  "status": "error",
  "message": "Invalid username/email or password.",
  "errors": ["Invalid username/email or password."]
}
```

---

## Future Enhancements

1. **JSON API Endpoints** – Convert form handlers to return JSON for AJAX-based frontends
2. **Email Verification** – Confirm email before account activation
3. **Password Reset** – Secure recovery flow with time-limited tokens
4. **Two-Factor Authentication** – OTP or TOTP for enhanced security
5. **Role-Based Access Control (RBAC)** – Admin, organizer, student roles
6. **Logout Handler** – Clear session and optional token blacklist
7. **Rate Limiting** – Prevent brute-force login attempts
8. **Audit Logging** – Track login/registration events for security analysis

---

## Quick Reference

| Component | File | Purpose |
|-----------|------|---------|
| DB Connection | `Database.php` | Manage Supabase connection |
| Auth Logic | `AuthManager.php` | Register/login users |
| Registration Form Handler | `register.php` | POST endpoint for signup |
| Login Form Handler | `login.php` | POST endpoint for signin |
| Schema | `supabase_auth_schema.sql` | Create users table |
| Config Template | `.env.example` | Environment variables template |

---

## References

- [Supabase PostgreSQL Documentation](https://supabase.com/docs/guides/database)
- [PHP pgsql Extension](https://www.php.net/manual/en/ref.pgsql.php)
- [bcrypt Password Hashing](https://www.php.net/manual/en/function.password-hash.php)
