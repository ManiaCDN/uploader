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

## Integration Test Architecture

### Test Organization
- Feature-based test organization in `tests/Integration/`
- Base class: `IntegrationTestCase` for common setup
- Test classes named by feature: `FileBrowsingTest`, `FileUploadTest`, etc.

### Virtual Filesystem
- To mock already uploaded files, use givenUploadedFiles()
- Internally uses `vfsStream` for file system isolation

### Database Testing
- SQLite in-memory database via `.env.test`: `DATABASE_URL="sqlite:///:memory:"`
- Clean up entities between tests

### Authentication
- Use Symfony's `loginUser()` to bypass OAuth2 in tests
- Create test users directly in database

### Common Test Patterns
- Arrange, act, assert structure
