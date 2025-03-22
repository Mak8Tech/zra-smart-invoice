# Changelog

All notable changes to the ZRA Smart Invoice package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Deployment Infrastructure**:
  - Added GitHub Actions workflow for running PHP tests
  - Added GitHub Actions workflow for automatic README badge updates
  - Created comprehensive deployment guide for Packagist.org in DEPLOYMENT.md
  - Added LICENSE.md file with MIT license
- **Backend Test Suite**:
  - Fixed ZraServiceTest by properly handling device_id in additional_config JSON field
  - Updated assertions to match actual implementation of transaction types ('sales' vs 'sales_data')
  - Made tests more resilient with conditional checks for field presence
  - Fixed transaction type naming in log assertions ('initialization' vs 'initialize_device')
  - Added proper null handling for JSON fields
- **Frontend Test Suite**:
  - Fixed StatusIndicator tests by using precise text selectors to avoid ambiguity
  - Resolved ConfigForm test failures by correctly handling Vitest's module mocking hoisting
  - Added proper TypeScript support with Jest DOM type extensions
  - Fixed React imports across all test files
  - Added setup file for proper TypeScript compatibility with testing-library
- **TypeScript Errors**:
  - Fixed route function declaration in ConfigForm component
  - Added type-safe handling for page.props.flash in ConfigForm component
  - Resolved undefined type error in StatusIndicator by using nullish coalescing operator
  - Fixed type declarations for React components
- Fixed Vitest configuration for frontend testing

### Changed
- **Documentation**:
  - Enhanced README.md with badges for build status, version, PHP compatibility, Laravel version, and license
  - Added clear instructions for installation and usage
  - Improved code examples with proper syntax highlighting

## [0.3.0] - 2025-03-22

### Added
- **Security Enhancements**:
  - Role-based access control with `ZraRoleMiddleware`
  - Rate limiting for API endpoints with `ZraRateLimitMiddleware`
  - Improved input validation in all request classes
- **Performance Improvements**:
  - Database indexing for better query performance
  - Caching system for configuration and statistics
  - Query optimization for transaction logs
- **Monitoring & Alerts**:
  - Comprehensive alert system for failed operations
  - Notifications via email and Slack
  - Threshold-based alerting for multiple failures
- **Development Environment**:
  - Docker support with docker-compose configuration
  - Nginx configuration for containerized development
  - Complete Dockerfile for PHP 8.2

### Changed
- **ZraService**:
  - Improved caching for frequently accessed data
  - Enhanced error handling and logging
  - Added statistics calculation with performance optimizations
- **Frontend Components**:
  - Updated ConfigForm with better UX for device initialization
  - Enhanced TransactionLog component for better readability
  - Improved styling and responsive design

### Fixed
- TypeScript linting errors in test files
- Added proper type declarations for React components
- Fixed Vitest configuration for frontend testing

## [0.2.0] - 2025-03-21

### Added
- **Frontend Testing Framework**:
  - Vitest setup for React component testing
  - TypeScript support for all tests
  - Comprehensive test coverage for all components
- **Frontend Components**:
  - DashboardWidget for monitoring ZRA integration status
  - StatusIndicator to display device status and environment
  - ConfigForm for device initialization and config management
  - TransactionLog to present recent transaction logs
- **Package Infrastructure**:
  - Complete package.json with all required dependencies
  - TypeScript configuration for frontend development
  - ESLint and Prettier setup for code quality

### Changed
- **Route Definitions**:
  - Added routes for statistics and health checks
  - Implemented transaction queuing endpoints
  - Improved route organization

### Fixed
- Various TypeScript linting issues
- Component rendering issues in React

## [0.1.0] - 2025-03-22

### Added
- Initial package structure setup
- Created composer.json with proper Laravel 12 dependencies
- Implemented ZraServiceProvider for package registration
- Set up autoloading configuration
- Created zra.php config file with environment variables support
- Implemented database migrations for configs and transaction logs
- Created ZraConfig and ZraTransactionLog models with relationships
- Implemented ZraService for core API operations
- Added HTTP client wrapper with error handling
- Created web routes and controller implementation
- Built React/TypeScript frontend components for configuration
- Added transaction log viewer component
- Implemented status indicators for device initialization
- Created device initialization form with validation
- Added test connection functionality
- Created DashboardWidget for monitoring ZRA integration status
- Implemented queue support for long-running operations
- Added ZraHealthCheckCommand for monitoring and maintenance
- Enhanced ZraService with statistics and health check methods
- Added frontend tab navigation system with Dashboard tab
- Created comprehensive package.json with all required dependencies
- Implemented Vitest configuration for frontend testing
- Added unit tests for all React components

### Improved
- Enhanced error handling in API communications
- Implemented queue-based transaction processing for better performance
- Added support for background processing of API requests
- Enhanced controller with statistics and health check endpoints
