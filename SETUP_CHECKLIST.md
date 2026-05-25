# Symfony 6.1 Setup Checklist

This checklist helps new team members set up the development environment correctly.

## ✅ Prerequisites

- [ ] PHP 8.2+ installed
  ```bash
  php --version
  ```
  
- [ ] Composer installed
  ```bash
  composer --version
  ```

- [ ] Git installed
  ```bash
  git --version
  ```

## ✅ Repository Setup

- [ ] Clone the repository
  ```bash
  git clone <repository-url>
  cd Web-Dev-Project
  ```

- [ ] Checkout the `ver/symfony` branch
  ```bash
  git checkout ver/symfony
  ```

- [ ] Verify you're on the correct branch
  ```bash
  git branch  # Should show * ver/symfony
  ```

## ✅ Dependency Installation

- [ ] Install Composer dependencies
  ```bash
  composer install
  ```

- [ ] Verify installation
  ```bash
  ls -la vendor/
  ```

## ✅ Environment Configuration

- [ ] Copy environment template
  ```bash
  cp .env.local.example .env.local
  ```

- [ ] Edit `.env.local` with your settings
  ```bash
  nano .env.local
  # or use your preferred editor
  ```

- [ ] Verify required environment variables:
  - [ ] `APP_ENV=dev` (for development)
  - [ ] `APP_DEBUG=true` (for development)
  - [ ] `APP_SECRET` (unique secret key)
  - [ ] `SUPABASE_URL` (Supabase project URL)
  - [ ] `SUPABASE_ANON_KEY` (Supabase anon key)
  - [ ] `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

## ✅ Database Setup

- [ ] Verify database credentials in `.env.local`

- [ ] Create the database (if using Supabase)
  ```bash
  php bin/console doctrine:database:create
  ```

- [ ] (Optional) Run migrations
  ```bash
  php bin/console doctrine:migrations:migrate
  ```

## ✅ Start Development

- [ ] Clear cache
  ```bash
  php bin/console cache:clear
  ```

- [ ] Start Symfony server
  ```bash
  symfony server:start
  ```

- [ ] Open application in browser
  - Navigate to `http://localhost:8000`
  - You should see the homepage or API welcome page

## ✅ Verification

- [ ] Application loads without errors
- [ ] Homepage displays correctly
- [ ] Database connection works (check logs if issues)
- [ ] Can access Symfony Profiler via `http://localhost:8000/_profiler`

## ✅ IDE Setup (Optional but Recommended)

### VS Code
- [ ] Install PHP Intelephense extension
- [ ] Install Symfony Support extension
- [ ] Install Twig extension

### PhpStorm
- [ ] Configure Symfony plugin
- [ ] Index Composer packages
- [ ] Set PHP language level to 8.2+

## ✅ Git Workflow

- [ ] Create a feature branch for your work
  ```bash
  git checkout -b feature/your-feature-name
  ```

- [ ] Configure git user (if not already done)
  ```bash
  git config --global user.name "Your Name"
  git config --global user.email "your.email@example.com"
  ```

## 📝 Common Issues

### "Command not found: symfony"
- Install Symfony CLI: https://symfony.com/download

### "Class not found" errors
```bash
composer dump-autoload
php bin/console cache:clear
```

### Port 8000 already in use
```bash
symfony server:start --port=8001
```

### Database connection failed
- Verify `.env.local` has correct credentials
- Check Supabase project is running
- Verify network connectivity

## 📚 Next Steps

1. Read [MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md) for detailed Symfony information
2. Review existing code in `src/` and `backend/`
3. Check open GitHub issues for tasks to work on
4. Follow the Contributing guidelines in README.md

## 🆘 Need Help?

- Check [MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md) for Symfony-specific questions
- Review [Symfony Documentation](https://symfony.com/doc/6.1/)
- Check existing code for examples of patterns used in the project
- Ask the team in your communication channel

---

Once you've completed this checklist, you're ready to start contributing! 🚀
