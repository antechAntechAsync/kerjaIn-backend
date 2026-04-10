# Code Quality Setup - KerjaIn Backend

This document describes the code quality tools and configuration for the KerjaIn backend project.

## 📋 Tools Installed

### 1. **PHP Code Sniffer (PHPCS)**
- **Purpose**: Detects coding standard violations
- **Configuration**: `phpcs.xml.dist`
- **Usage**:
  ```bash
  # Check for violations
  npm run lint:php
  
  # Auto-fix violations
  npm run lint:php:fix
  ```

### 2. **PHP-CS-Fixer**
- **Purpose**: Automatically fixes coding style issues
- **Configuration**: `.php-cs-fixer.dist.php`
- **Usage**:
  ```bash
  npm run format:php
  ```

### 3. **PHPStan**
- **Purpose**: Static analysis tool for finding bugs
- **Configuration**: `phpstan.neon.dist`
- **Usage**:
  ```bash
  npm run analyze:php
  ```

### 4. **Laravel Pint**
- **Purpose**: Laravel's official code style fixer
- **Configuration**: `pint.json`
- **Usage**:
  ```bash
  npm run format:pint
  ```

### 5. **Husky & lint-staged**
- **Purpose**: Git hooks for automatic code quality checks
- **Configuration**: `.husky/`, `package.json`
- **Hooks**:
  - **pre-commit**: Runs formatters on staged files
  - **pre-push**: Runs full quality checks before pushing

## 🚀 Quick Start Commands

```bash
# Install all dependencies
composer install
npm install

# Run all code quality checks
npm run test:quality

# Format all PHP files
npm run format:all

# Fix linting issues automatically
npm run lint:php:fix
```

## 🔧 Editor Configuration (VS Code)

### Recommended Extensions
Install the extensions listed in `.vscode/extensions.json` for the best development experience.

### Key Features
1. **Format on Save**: Automatically formats PHP files on save
2. **Code Actions**: Auto-fix imports and fix all issues on save
3. **PHP IntelliSense**: Advanced PHP code completion
4. **Real-time Linting**: Shows errors as you type

### VS Code Tasks
Use `Ctrl+Shift+P` → `Tasks: Run Task` to access:
- `PHP: Run Code Quality Check`
- `PHP: Format All Files`
- `PHP: Fix Linting Issues`
- `Laravel: Run Tests`

## 📁 Configuration Files

| File | Purpose |
|------|---------|
| `.php-cs-fixer.dist.php` | PHP-CS-Fixer rules |
| `phpstan.neon.dist` | PHPStan static analysis configuration |
| `pint.json` | Laravel Pint configuration |
| `phpcs.xml.dist` | PHP Code Sniffer rules |
| `.vscode/settings.json` | VS Code workspace settings |
| `.vscode/extensions.json` | Recommended VS Code extensions |
| `.vscode/tasks.json` | VS Code task definitions |
| `.husky/pre-commit` | Git pre-commit hook |
| `.husky/pre-push` | Git pre-push hook |
| `package.json` | NPM scripts and lint-staged config |

## 🎯 Coding Standards

### PSR Compliance
- **PSR-1**: Basic Coding Standard
- **PSR-12**: Extended Coding Style Guide
- **PSR-4**: Autoloading Standard

### Key Rules
1. **Indentation**: 4 spaces (no tabs)
2. **Line Length**: Maximum 120 characters
3. **Braces**: K&R style
4. **Namespaces**: One namespace per file
5. **Use Statements**: Alphabetical order
6. **Visibility**: Always declare (public, protected, private)
7. **Type Declarations**: Use strict typing where possible
8. **DocBlocks**: Required for public methods

### Laravel Conventions
- Follow Laravel's naming conventions
- Use Laravel's directory structure
- Implement repository pattern for complex queries
- Use service classes for business logic

## 🔍 Git Workflow

### Pre-commit
1. Automatically formats staged PHP files
2. Runs PHP-CS-Fixer and Laravel Pint
3. Fixes PHP Code Sniffer violations

### Pre-push
1. Runs full PHP Code Sniffer check
2. Executes PHPStan static analysis
3. Blocks push if issues are found

## 🐛 Troubleshooting

### Common Issues

1. **"PHP-CS-Fixer not found"**
   ```bash
   composer require --dev friendsofphp/php-cs-fixer
   ```

2. **"PHPStan memory limit exceeded"**
   - Increase memory limit in `phpstan.neon.dist`
   - Run with `--memory-limit=2G`

3. **"Husky hooks not running"**
   ```bash
   npx husky install
   chmod +x .husky/*
   ```

4. **"VS Code not formatting on save"**
   - Ensure PHP-CS-Fixer extension is installed
   - Check workspace settings in `.vscode/settings.json`

### Performance Tips
- Use `--no-progress` flag for faster execution in CI
- Exclude vendor and storage directories from analysis
- Use cache files for PHP-CS-Fixer and PHPStan

## 📊 Continuous Integration

Add these steps to your CI pipeline:

```yaml
quality-checks:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    - run: composer install --no-progress --no-suggest
    - run: npm ci
    - run: npm run test:quality
```

## 🔄 Updating Rules

To modify coding standards:

1. Edit the respective configuration file
2. Test changes locally
3. Run `npm run format:all` to apply to existing code
4. Commit the configuration changes

## 📚 Resources

- [PHP-FIG Standards](https://www.php-fig.org/psr/)
- [Laravel Coding Standards](https://laravel.com/docs/contributions#coding-style)
- [PHP-CS-Fixer Documentation](https://cs.symfony.com/)
- [PHPStan Documentation](https://phpstan.org/user-guide/)
- [Laravel Pint Documentation](https://laravel.com/docs/pint)
