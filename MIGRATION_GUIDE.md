# Symfony 6.1 Migration Guide

This project has been migrated from a custom PHP architecture to Symfony 6.1. This guide explains the new structure and how to work with it.

## New Directory Structure

```
project-root/
├── bin/
│   └── console           # Symfony CLI entry point
├── config/
│   ├── bundles.php       # Bundle configuration
│   ├── services.yaml     # Service container configuration
│   ├── routes.yaml       # Route definitions
│   └── packages/         # Individual package configs
├── public/
│   ├── index.php         # Application entry point
│   └── assets/           # Static files
├── src/
│   ├── Controller/       # API/Web Controllers
│   ├── Entity/           # Doctrine entities
│   ├── Repository/       # Database repositories
│   ├── Service/          # Business logic services
│   └── Kernel.php        # Application Kernel
├── templates/            # Twig templates
├── tests/                # Unit/Functional tests
├── backend/              # Legacy code (PSR-4 namespace: Legacy\)
├── pages/                # Legacy HTML pages
├── scripts/              # Legacy JavaScript
├── styles/               # Legacy CSS
├── var/
│   ├── cache/            # Application cache
│   └── log/              # Application logs
└── vendor/               # Composer dependencies
```

## Getting Started

### 1. Install Dependencies
```bash
composer install
```

### 2. Set Up Environment
```bash
cp .env.local.example .env.local
# Edit .env.local with your database and Supabase credentials
```

### 3. Run the Development Server
```bash
symfony server:start
```

The application will be available at `http://localhost:8000`

## Key Features

### PSR-4 Autoloading

Both new and legacy code are autoloaded:
- **App\\** → `src/` (New Symfony code)
- **Legacy\\** → `backend/` (Legacy PHP code for gradual migration)

### Configuration

- **Environment Variables**: Define in `.env` and override in `.env.local`
- **Supabase Integration**: Configure via environment variables (see `.env.local.example`)
- **Service Container**: Configure services in `config/services.yaml`

### Creating Controllers

Controllers handle HTTP requests. Place them in `src/Controller/`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api/endpoint', methods: ['GET'])]
    public function getEndpoint(): Response
    {
        return $this->json(['message' => 'Hello World']);
    }
}
```

### Creating Services

Services contain your business logic. Place them in `src/Service/`:

```php
<?php

namespace App\Service;

class ClubService
{
    public function getClubs(): array
    {
        // Business logic here
    }
}
```

### Using Dependency Injection

Symfony's service container automatically injects dependencies:

```php
<?php

namespace App\Controller;

use App\Service\ClubService;

class ClubController
{
    public function __construct(
        private ClubService $clubService,
    ) {}

    public function list()
    {
        return $this->clubService->getClubs();
    }
}
```

## Database Setup

### With Supabase
Update `.env.local` with your Supabase credentials, then run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Legacy Database Files
- `backend/supabase_auth_schema.sql`
- `backend/supabase_content_schema_and_seed.sql`

These can be imported directly into your database.

## Migrating Legacy Code

### Option 1: Gradual Migration
Keep old code in `backend/` (namespace `Legacy\`) while building new features in `src/`.

### Option 2: Refactor to Symfony Patterns
- Convert controllers to `src/Controller/`
- Move business logic to `src/Service/`
- Create entities in `src/Entity/`
- Define routes in `config/routes.yaml`

## CLI Commands

Common Symfony console commands:

```bash
# List all available commands
php bin/console list

# Cache clear
php bin/console cache:clear

# Create a new controller
php bin/console make:controller

# Create a migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Run tests
php bin/phpunit
```

## Static Files

Place static files (CSS, JS, images) in `public/`:
- `public/css/`
- `public/js/`
- `public/images/`

Reference them in templates:
```html
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<script src="{{ asset('js/app.js') }}"></script>
```

## Debugging

### Profiler
Access the Symfony profiler at `/_profiler` (development only) to debug requests, database queries, and performance.

### Logs
Application logs are stored in `var/log/`. Common files:
- `var/log/dev.log` (development)
- `var/log/test.log` (testing)

## Useful Resources

- [Symfony Documentation](https://symfony.com/doc/6.1/)
- [Symfony Best Practices](https://symfony.com/doc/6.1/best_practices.html)
- [Doctrine ORM Guide](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/index.html)

## Troubleshooting

### Composer dependency issues
```bash
composer update
composer dump-autoload
```

### Cache issues
```bash
rm -rf var/cache/*
php bin/console cache:clear
```

### Database connection issues
- Verify `.env.local` has correct database credentials
- Ensure Supabase project is running
- Check that database user has correct permissions

## Next Steps for Team

1. Clone the repository with the new branch
2. Run `composer install`
3. Copy `.env.local.example` to `.env.local` and configure
4. Run `symfony server:start`
5. Test accessing `http://localhost:8000`
6. Begin migrating legacy code to Symfony structure
