# KerjaIn Backend - Project Context

## Project Overview

KerjaIn is a Laravel 12-based backend API for a career guidance and learning platform. The application provides comprehensive tools for students to assess their interests, skills, and knowledge, receive career recommendations, track learning progress, and follow personalized learning roadmaps. It also includes features for human resource management (job listings, employee management).

### Core Technologies

- **Framework**: Laravel 12 (PHP 8.2+)
- **Authentication**: Laravel Sanctum (API tokens), Laravel Socialite (Google OAuth)
- **Database**: SQLite (default), with support for MySQL, PostgreSQL
- **Data Handling**: Spatie Laravel Data (DTOs)
- **Testing**: PHPUnit 11.5
- **Development Tools**: Laravel Pail (logs), Laravel Pint (code style), Laravel Sail (Docker)

### Architecture

The application follows Laravel's MVC architecture with domain-based organization:

- **Controllers**: Organized by domain (`Auth`, `Student`, with placeholders for `HumanResource`)
- **Models**: Comprehensive data models for assessments, careers, roadmaps, users, and progress tracking
- **API Routes**: RESTful API endpoints in `routes/api.php`
- **Middleware**: Sanctum authentication for protected routes

### Key Features

1. **Authentication System**
   - Email/password registration and login
   - Google OAuth integration
   - Password reset functionality
   - Sanctum-based API authentication

2. **Student Assessment Modules**
   - Interest Assessment: Career interest profiling with questions and recommendations
   - Self Assessment: Skills evaluation
   - Knowledge Check: Subject knowledge verification

3. **Career Guidance**
   - Career recommendation engine based on assessment results
   - Interest field and subfield categorization
   - Career role matching

4. **Learning Management**
   - Roadmap generation and tracking
   - Progress monitoring per learning node
   - User progress history

5. **Human Resource Features** (Partially implemented)
   - Employee management
   - Job listing system

## Building and Running

### Initial Setup

```bash
# Install dependencies and set up the project
composer setup
```

This command will:
- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install and build frontend assets

### Development Server

```bash
# Start development server with queue, logs, and Vite
composer dev
```

This runs four concurrent processes:
- PHP development server (port 8000)
- Queue worker
- Log monitoring (Pail)
- Vite development server

### Individual Commands

```bash
# Start only the PHP development server
php artisan serve

# Run database migrations
php artisan migrate

# Run database seeders
php artisan db:seed

# Clear configuration cache
php artisan config:clear

# Clear application cache
php artisan cache:clear
```

### Testing

```bash
# Run all tests
composer test

# Or directly with PHPUnit
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

### Code Quality

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

## Development Conventions

### Code Style

- **Indentation**: 4 spaces (configured in `.editorconfig`)
- **Line Endings**: LF (Unix-style)
- **Encoding**: UTF-8
- **PHP Version**: 8.2+
- **PSR Standards**: PSR-4 autoloading

### File Organization

- **Controllers**: `app/Http/Controllers/{Domain}/`
  - Example: `app/Http/Controllers/Student/InterestAssessmentController.php`
- **Models**: `app/Models/`
- **Requests**: `app/Http/Requests/`
- **Middleware**: `app/Http/Middleware/`
- **Migrations**: `database/migrations/`
- **Factories**: `database/factories/`
- **Seeders**: `database/seeders/`
- **Tests**: `tests/Feature/` and `tests/Unit/`

### API Design

- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Bearer tokens via Sanctum (`Authorization: Bearer {token}`)
- **Response Format**: JSON
- **Route Prefixes**:
  - `/student/*` - Student-specific endpoints (requires authentication)
  - `/human-resource/*` - HR-specific endpoints (requires authentication)
  - `/auth/*` - Authentication endpoints

### Database Conventions

- **Default Connection**: SQLite
- **Migration Naming**: Descriptive, snake_case (e.g., `create_users_table`)
- **Model Naming**: Singular, PascalCase (e.g., `User`, `CareerRecommendation`)
- **Foreign Keys**: Follow Laravel conventions (`{table}_id`)

### Testing Conventions

- **Test Organization**: Separate Feature and Unit test suites
- **Test Database**: In-memory SQLite for testing
- **Environment**: `APP_ENV=testing` configured in `phpunit.xml`

### Key Models

- **User**: User accounts and authentication
- **StudentProfile**: Student-specific information
- **InterestSession/InterestResult**: Interest assessment data
- **SkillAssessment/SkillAssessmentAnswer**: Skills evaluation
- **KnowledgeCheckAttempt/KnowledgeCheckQuestion**: Knowledge verification
- **CareerRecommendation/CareerRole**: Career matching system
- **Roadmap/RoadmapNode**: Learning paths
- **UserProgress**: Progress tracking

### Environment Configuration

Key environment variables (see `.env.example`):
- `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION` (sqlite, mysql, pgsql)
- `SESSION_DRIVER` (database)
- `QUEUE_CONNECTION` (database)
- `CACHE_STORE` (database)
- `MAIL_MAILER` (log for development)

### Git Workflow

- **Branch**: Main development branch
- **Commit Messages**: Follow conventional commits (implied from Laravel standards)
- **Ignored Files**: `.gitignore` includes vendor, node_modules, .env, storage files

## Project-Specific Notes

1. **Assessment Flow**: Students complete interest, skills, and knowledge assessments which feed into the career recommendation engine
2. **Progress Tracking**: Users can mark roadmap nodes as complete and track overall progress
3. **Multi-Role Support**: Architecture supports both student and human resource roles
4. **OAuth Integration**: Google authentication is fully implemented
5. **Queue System**: Background jobs are configured for async processing
6. **Logging**: Laravel Pail is configured for real-time log monitoring during development

## Common Tasks

### Adding a New Assessment Type

1. Create model in `app/Models/`
2. Create migration in `database/migrations/`
3. Create controller in `app/Http/Controllers/Student/`
4. Add routes in `routes/api.php` under `/student` prefix
5. Create tests in `tests/Feature/`

### Adding API Endpoints

1. Create controller method
2. Add route in `routes/api.php` with appropriate middleware
3. Create form request validation in `app/Http/Requests/` (if needed)
4. Write feature tests

### Database Changes

```bash
# Create migration
php artisan make:migration create_table_name

# Run migration
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (warning: drops all tables)
php artisan migrate:fresh
```

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Spatie Laravel Data](https://spatie.be/docs/laravel-data)
