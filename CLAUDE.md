# CLAUDE.md - AI Assistant Guide

## Project Overview

**Project Name:** chat-app
**Type:** Laravel-based Chat Application
**Current Status:** Initial setup phase
**Primary Language:** PHP (Laravel Framework)
**Git Branch Strategy:** Feature branches with `claude/` prefix

---

## Repository Structure

This is a Laravel project. Once fully initialized, it will follow the standard Laravel directory structure:

```
chat-app/
├── app/                    # Application core
│   ├── Console/           # Artisan commands
│   ├── Exceptions/        # Exception handlers
│   ├── Http/              # Controllers, middleware, requests
│   │   ├── Controllers/   # Application controllers
│   │   ├── Middleware/    # HTTP middleware
│   │   └── Requests/      # Form request validation
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Database migrations, seeders, factories
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── public/                # Public web root
│   ├── css/              # Compiled CSS
│   ├── js/               # Compiled JavaScript
│   └── index.php         # Entry point
├── resources/             # Views, raw assets
│   ├── css/              # Raw CSS/SCSS
│   ├── js/               # Raw JavaScript/Vue/React
│   └── views/            # Blade templates
├── routes/                # Route definitions
│   ├── api.php           # API routes
│   ├── channels.php      # Broadcast channels
│   ├── console.php       # Console routes
│   └── web.php           # Web routes
├── storage/               # Logs, cache, compiled views
│   ├── app/              # Application storage
│   ├── framework/        # Framework cache/sessions
│   └── logs/             # Application logs
├── tests/                 # Automated tests
│   ├── Feature/          # Feature tests
│   └── Unit/             # Unit tests
├── vendor/                # Composer dependencies (not committed)
├── .env                   # Environment configuration (not committed)
├── .env.example           # Example environment file
├── artisan                # Artisan CLI
├── composer.json          # PHP dependencies
├── package.json           # Node.js dependencies
├── phpunit.xml            # PHPUnit configuration
└── vite.config.js         # Vite build configuration
```

---

## Development Workflow

### Prerequisites

Before working on this project, ensure:
- PHP 8.1+ is installed
- Composer is available
- Node.js and npm/yarn are installed
- MySQL/PostgreSQL database is configured
- Redis (optional, for queue/cache/broadcasting)

### Initial Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run dev
```

### Running the Application

```bash
# Start Laravel development server
php artisan serve

# In a separate terminal, watch for asset changes
npm run dev

# For queue workers (if using queues)
php artisan queue:work

# For WebSocket server (if using Laravel Echo/Pusher)
php artisan websockets:serve
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage

# Run PHPUnit directly
./vendor/bin/phpunit
```

---

## Key Technologies & Patterns

### Laravel Framework
- **Version:** TBD (likely Laravel 10+)
- **PHP Version:** 8.1+
- **Architecture:** MVC (Model-View-Controller)

### Expected Chat Application Features
- Real-time messaging (Laravel Echo, Pusher, or WebSockets)
- User authentication and authorization
- Message persistence
- Chat rooms or channels
- User presence indicators
- Message notifications

### Common Laravel Patterns

#### Controllers
- Keep controllers thin
- Use Form Requests for validation
- Return views or JSON responses
- Utilize resource controllers for RESTful APIs

#### Models
- Use Eloquent ORM
- Define relationships clearly
- Use accessors/mutators for data transformation
- Implement model events when needed

#### Routing
- Group related routes
- Apply middleware appropriately
- Use route model binding
- Name routes for easy referencing

#### Database
- Always create migrations for schema changes
- Use seeders for sample data
- Utilize factories for testing
- Follow naming conventions (plural table names)

---

## Coding Conventions

### PHP Standards
- Follow PSR-12 coding standards
- Use type hints for parameters and return types
- Write descriptive method and variable names
- Use camelCase for methods, PascalCase for classes
- Document complex logic with PHPDoc comments

### Laravel-Specific
- Use Eloquent over raw queries when possible
- Leverage service containers and dependency injection
- Create service classes for complex business logic
- Use form requests for validation
- Utilize Laravel collections for data manipulation
- Follow repository pattern for data access when needed

### JavaScript/Frontend
- Use modern ES6+ syntax
- Follow Vue.js/React conventions if using a framework
- Keep components modular and reusable
- Use Vite for asset bundling

### Database
- **Table naming:** Plural, snake_case (e.g., `chat_messages`)
- **Column naming:** snake_case (e.g., `created_at`, `user_id`)
- **Primary keys:** `id` (auto-incrementing)
- **Foreign keys:** `{table_singular}_id` (e.g., `user_id`)
- **Timestamps:** Use `timestamps()` in migrations
- **Soft deletes:** Use `softDeletes()` when needed

### Testing
- Write feature tests for user workflows
- Write unit tests for business logic
- Use factories for test data
- Mock external services
- Aim for meaningful test coverage

---

## Git Workflow

### Branch Naming
- Feature branches: `claude/claude-md-{session-id}`
- Development branch: As specified per feature
- **CRITICAL:** All development must occur on the designated feature branch

### Commit Messages
- Use clear, descriptive commit messages
- Follow conventional commits format:
  - `feat:` for new features
  - `fix:` for bug fixes
  - `refactor:` for code refactoring
  - `test:` for adding tests
  - `docs:` for documentation changes
  - `chore:` for maintenance tasks

### Push Protocol
```bash
# Always push with upstream tracking
git push -u origin <branch-name>

# Branch must start with 'claude/' and match session ID
# Retry on network failures with exponential backoff
```

---

## AI Assistant Guidelines

### When Making Changes

1. **Read Before Writing**
   - Always read files before editing
   - Understand existing patterns and conventions
   - Maintain consistency with existing code style

2. **Use Appropriate Tools**
   - Use `Read` for viewing files
   - Use `Edit` for modifying existing files
   - Use `Write` only for new files
   - Use `Bash` for Laravel artisan commands and git operations

3. **Laravel-Specific Commands**
   ```bash
   # Create new controller
   php artisan make:controller ChatController

   # Create new model with migration
   php artisan make:model Message -m

   # Create new migration
   php artisan make:migration create_messages_table

   # Create new form request
   php artisan make:request StoreMessageRequest

   # Create new test
   php artisan make:test MessageTest

   # Clear caches
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Security Considerations**
   - Always validate user input
   - Use Laravel's built-in CSRF protection
   - Sanitize output to prevent XSS
   - Use parameterized queries (Eloquent does this by default)
   - Never commit `.env` files
   - Use Laravel's authentication and authorization features
   - Hash passwords with bcrypt
   - Protect against SQL injection
   - Implement rate limiting for APIs

5. **Testing Requirements**
   - Write tests for new features
   - Update tests when modifying existing features
   - Ensure tests pass before committing
   - Use database transactions in tests for clean state

6. **Documentation**
   - Update this CLAUDE.md when project structure changes
   - Add PHPDoc comments for complex methods
   - Update README.md for user-facing changes
   - Document API endpoints clearly

### Task Planning

For complex tasks:
1. Use `TodoWrite` to break down tasks into steps
2. Mark tasks as in_progress before starting
3. Complete one task before moving to the next
4. Mark tasks as completed immediately upon finishing

### Common Pitfalls to Avoid

- Don't modify files without reading them first
- Don't commit vendor/ or node_modules/ directories
- Don't hardcode configuration values (use .env)
- Don't skip migrations when changing database schema
- Don't forget to clear Laravel caches after config changes
- Don't use raw SQL when Eloquent can handle it
- Don't forget to run `composer dump-autoload` after creating new classes
- Don't push to the wrong branch

### File Permissions

Laravel requires specific permissions:
```bash
# Storage and cache directories need write permissions
chmod -R 775 storage bootstrap/cache
```

---

## Environment Configuration

### Required Environment Variables (.env)

```env
APP_NAME=ChatApp
APP_ENV=local
APP_KEY=                    # Generated by php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_app
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher     # For real-time chat
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

---

## Resources

### Laravel Documentation
- Official Docs: https://laravel.com/docs
- Laravel News: https://laravel-news.com
- Laracasts: https://laracasts.com

### Real-time Chat Resources
- Laravel Echo: https://laravel.com/docs/broadcasting
- Laravel WebSockets: https://beyondco.de/docs/laravel-websockets
- Pusher: https://pusher.com/docs

### Testing
- Laravel Testing: https://laravel.com/docs/testing
- Pest PHP: https://pestphp.com (alternative to PHPUnit)

---

## Current Project Status

**Last Updated:** 2025-11-16

### Completed
- Initial repository setup
- README.md created
- License added

### Pending
- Laravel installation
- Database setup
- Authentication system
- Chat functionality implementation
- Real-time messaging setup
- Frontend framework integration
- Testing suite setup

---

## Notes for Future AI Assistants

This project is in its initial stages. When working on this codebase:

1. **First-time setup:** If Laravel isn't installed yet, ask the user if they want to initialize it with `composer create-project laravel/laravel .` or if they'll handle it manually.

2. **Chat-specific features:** Consider the following architecture decisions:
   - Will this use REST API + polling, or WebSockets for real-time updates?
   - Single chat room or multiple rooms/channels?
   - Private messaging support?
   - File/image sharing in messages?
   - Message search and history?

3. **Scalability:** Plan for:
   - Message pagination
   - Efficient database indexing
   - Caching frequently accessed data
   - Queue-based processing for heavy operations

4. **User experience:**
   - Typing indicators
   - Read receipts
   - Online/offline status
   - Message notifications
   - Mobile responsiveness

Always ask for clarification on feature requirements before implementing to ensure alignment with project goals.

---

*This document should be updated as the project evolves and new conventions are established.*
