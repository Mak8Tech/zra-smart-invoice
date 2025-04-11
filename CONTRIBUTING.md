# Contributing to ZRA Smart Invoice

Thank you for considering contributing to the ZRA Smart Invoice package! This document outlines the process for contributing to this project.

## Code of Conduct

This project adheres to a Code of Conduct. By participating, you are expected to uphold this code.

## Pull Request Process

1. Fork the repository.
2. Create a new branch from `main` for your changes.
3. Make your changes, following the code style of the project.
4. Add tests if applicable.
5. Update documentation if needed.
6. Ensure all tests pass by running `composer test`.
7. Commit with clear, descriptive messages.
8. Push to your fork and submit a pull request.

## Development Setup

1. Clone your fork of the repository.
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Set up the testing environment:
   ```bash
   cp .env.example .env.testing
   ```
4. Run tests to make sure everything is working:
   ```bash
   composer test
   npm run test
   ```

## Testing

- All changes must be covered by tests.
- Run the PHP tests with `composer test`.
- Run JavaScript tests with `npm run test`.

## Coding Standards

- PHP code follows PSR-12 coding standards.
- JavaScript/TypeScript code follows the project's ESLint configuration.
- Use type hints where possible in PHP.
- Use TypeScript for all React components.

## Documentation

If you're adding new features or making changes that require documentation updates:

1. Update relevant README.md sections.
2. Update docs/ folder with any specialized documentation.
3. Add PHPDoc blocks to all new classes and methods.
4. Document any new configuration options.

## Versioning

This project follows [Semantic Versioning](https://semver.org/).

## Release Process

The package maintainers will handle releases, following this process:

1. Update CHANGELOG.md with all notable changes.
2. Update version numbers in relevant files.
3. Create a new GitHub release.
4. Publish to Packagist.

## Questions?

If you have any questions or need help, please open an issue on GitHub.

Thank you for contributing to the ZRA Smart Invoice package!
