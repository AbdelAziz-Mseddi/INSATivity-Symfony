# INSATivity - Symfony Monolith Migration

This directory contains the migrated Symfony 6.4 full-stack version of the **INSATivity** event management platform.

## Setup Instructions

### 1. Copy Frontend Assets & Scripts
Since we are migrating to a Symfony structure, all static files must be placed inside the `public/` folder.
Please copy the directories from the original workspace:
*   Copy `styles/` folder -> `symfony_version/public/styles/`
*   Copy `scripts/` folder -> `symfony_version/public/scripts/`
*   Copy `assets/` folder -> `symfony_version/public/assets/`

> [!WARNING]
> We have pre-configured `symfony_version/public/scripts/config.js` with `API_BASE_URL: ''` to target the Symfony routes. **Do not overwrite `public/scripts/config.js`** when copying the scripts folder, or restore it to:
> ```javascript
> export const CONFIG = { API_BASE_URL: '' };
> ```

---

### 2. Install Dependencies
Run composer to install all Symfony components and Doctrine ORM dependencies:
```bash
cd symfony_version
composer install
```

---

### 3. Database Configurations
The database credentials have been automatically loaded from your original setup into `symfony_version/.env`.
Ensure `DATABASE_URL` is correct:
```env
DATABASE_URL=postgresql://postgres.nomzklrmetorwthsyvdq:A5clE11bAHYu94E1@aws-1-eu-west-1.pooler.supabase.com:5432/postgres?sslmode=require
```

---

### 4. Run the Application
Start the PHP built-in server from the `symfony_version/` directory:
```bash
php -S localhost:8000 -t public
```
Open **`http://localhost:8000/`** in your browser!

---

## Architectural Highlights

*   **Server-Side Rendering (Twig):** Main listings (featured and upcoming events on the homepage, clubs directory on the clubs page) are rendered directly on the server.
*   **Doctrine ORM:** Custom raw-SQL queries have been translated into modern PHP Entities (`src/Entity/`) and Repositories (`src/Repository/`).
*   **Unified Routing:** Replaced action parameters (`?action=getAll`) and individual PHP endpoint scripts with standard Symfony Controllers using Attributes (`src/Controller/`).
