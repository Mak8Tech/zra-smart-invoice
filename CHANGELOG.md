# Changelog

All notable changes to the ZRA Smart Invoice package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Security Enhancements (REQ-005)
- User Interface Improvements (REQ-006)
- Documentation (REQ-007)
- Testing (REQ-008)
- Performance Optimization (REQ-009)
- Error Handling and Logging (REQ-010)

## [1.0.1] - 2023-12-06

### Added

- **Support for Various Invoice Types (REQ-001)**:

  - Added configuration options for invoice types (NORMAL, COPY, TRAINING, PROFORMA)
  - Added support for transaction types (SALE, CREDIT_NOTE, DEBIT_NOTE, ADJUSTMENT, REFUND)
  - Updated ZraService to accept and validate invoice and transaction types
  - Enhanced ConfigForm UI with dropdown selectors for invoice and transaction types
  - Updated controller to pass invoice and transaction type parameters

- **Comprehensive Tax Handling (REQ-002)**:

  - Added ZraTaxService for tax calculations based on different tax categories
  - Implemented support for multiple tax categories (VAT, Tourism Levy, Excise Duty)
  - Added zero-rated and exempt transaction handling
  - Updated UI to include tax category selection
  - Added tax calculation API endpoints
  - Implemented tax summary display in test results

- **Report Generation (X and Z Reports) (REQ-003)**:

  - Added ZraReportCommand for generating reports via CLI
  - Created ZraReportService for generating X, Z, daily, and monthly reports
  - Enhanced DashboardWidget with report generation UI
  - Added report generation API endpoint
  - Implemented report export in various formats (JSON, text)
  - Added support for saving reports to disk

- **Inventory Management Integration (REQ-004)**:
  - Created ZraInventoryService for inventory tracking and management
  - Implemented database migrations for inventory and inventory movements tables
  - Added ZraInventory and ZraInventoryMovement models with relationships
  - Created comprehensive React component for inventory management UI
  - Added product management functionality (create, update, delete)
  - Implemented stock level tracking and validation
  - Added stock movement history and reporting
  - Integrated inventory validation with sales transactions

## [1.0.0] - 2023-12-01

### Added

- **Initial Release**:

  - Initial package structure setup
  - Created composer.json with proper Laravel dependencies
  - Implemented ZraServiceProvider for package registration
  - Set up autoloading configuration
  - Created zra.php config file with environment variables support
  - Implemented database migrations for configs and transaction logs
  - Created ZraConfig and ZraTransactionLog models with relationships
  - Implemented ZraService for core API operations
  - Added HTTP client wrapper with error handling
  - Created web routes and controller implementation

- **Frontend Components**:

  - Built React/TypeScript frontend components for configuration
  - Added transaction log viewer component
  - Implemented status indicators for device initialization
  - Created device initialization form with validation
  - Added test connection functionality
  - Created DashboardWidget for monitoring ZRA integration status
  - StatusIndicator to display device status and environment
  - ConfigForm for device initialization and config management
  - TransactionLog to present recent transaction logs

- **API Features**:

  - Support for various invoice types
  - Comprehensive tax handling
  - Security enhancements with proper encryption
  - Error handling and detailed logging

- **Testing and Development**:

  - Frontend Testing Framework with Vitest
  - TypeScript support for all tests
  - Unit tests for React components
  - Complete package.json with all required dependencies
  - TypeScript configuration for frontend development
  - ESLint and Prettier setup for code quality

- **Performance and Monitoring**:
  - Implemented queue support for long-running operations
  - ZraHealthCheckCommand for monitoring and maintenance
  - Enhanced ZraService with statistics and health check methods
  - Database indexing for better query performance
  - Comprehensive alert system for failed operations

### Improved

- Enhanced error handling in API communications
- Implemented queue-based transaction processing for better performance
- Added support for background processing of API requests
- Enhanced controller with statistics and health check endpoints
