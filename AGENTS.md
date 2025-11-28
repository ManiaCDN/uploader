# Project Overview

This is a community-powered CDN for Maniaplanet gaming platform. Users authenticate via OAuth2 with Maniaplanet accounts to upload files to shared folders. Files are blocked by default and require admin review before becoming publicly available. The system includes file browsing, user management, email notifications, and administrative review workflows.

# Build Commands
- **Run tests**: `vendor/bin/phpunit`
- **Run single test**: `vendor/bin/phpunit --filter TestName`
- **Static analysis**: `vendor/bin/phpstan analyse`
- **Clear cache**: `bin/console cache:clear`
- **Database migrations**: `bin/console doctrine:migrations:migrate`

# Code Style Guidelines

## File Structure
- No license headers in source files
- PSR-4 autoloading: `App\` namespace maps to `src/`
- Tests in `tests/` with `App\Tests\` namespace

## Naming Conventions
- Classes: PascalCase (e.g., `FilesystemManager`, `ManiaplanetUser`)
- Methods: camelCase with descriptive names
- Variables: camelCase, avoid abbreviations in new code
- Properties: camelCase with appropriate visibility

## Type Safety
- Use parameter type hints and return type declarations
- Entity properties use Doctrine ORM annotations
- Interface implementations for UserInterface, Serializable

## Documentation
- PHPDoc blocks for all classes and public methods
- Describe parameters, return values, and purpose
- Include usage examples for complex methods

## Symfony Patterns
- Constructor dependency injection
- Service autowiring enabled
- Use AbstractController for web controllers
- Flash messages for user feedback

## Error Handling
- Use exceptions for system errors
- Flash messages for user-facing errors
- Proper validation and security checks