# ZRA Smart Invoice Architecture Documentation

This document provides an overview of the architecture and design decisions for the ZRA Smart Invoice package.

## Overview

The ZRA Smart Invoice package is a Laravel integration that facilitates communication with the Zambia Revenue Authority (ZRA) API for electronic fiscal devices. The package follows a modular design with clear separation of concerns and adheres to Laravel best practices.

## Architectural Components

### 1. Service Provider

The `ZraServiceProvider` serves as the entry point for the package, handling:
- Registration of services in the IoC container
- Loading of routes, migrations, and configuration
- Publishing of assets and configuration files
- Registration of console commands

### 2. Models

Two primary models manage the data:

- **ZraConfig**: Stores device configuration and authentication details
  - Handles device initialization status
  - Manages environment settings (sandbox vs. production)
  - Tracks synchronization status with ZRA

- **ZraTransactionLog**: Records all API interactions
  - Logs requests, responses, and errors
  - Provides data for statistics and monitoring
  - Sanitizes sensitive information for security

### 3. Services

The core business logic resides in services:

- **ZraService**: Handles all API communication with ZRA
  - Device initialization
  - Sales, purchase, and stock data submission
  - Error handling and logging
  - Health checks and status verification
  - Statistics calculation

### 4. Controllers & Routes

The HTTP layer consists of:

- **ZraController**: Processes web requests and returns responses
  - Configuration management
  - Device initialization
  - Status checks
  - Transaction log viewing
  - Statistics retrieval

- **Routes**: Defined in `routes/web.php`
  - Protected with configurable middleware
  - Named routes for easy reference
  - REST-style endpoints

### 5. Frontend Components (React/TypeScript)

The UI elements are built with React and TypeScript:

- **Index**: Main container component with tab navigation
- **ConfigForm**: Manages device initialization
- **StatusIndicator**: Displays current device status
- **TransactionLog**: Shows transaction history
- **DashboardWidget**: Visualizes statistics and health metrics

### 6. Jobs & Queues

Asynchronous processing is handled via:

- **ProcessZraTransaction**: Queue job for handling long-running API requests
- Configurable retry settings
- Error handling and logging

### 7. Commands

CLI tooling for management and monitoring:

- **ZraHealthCheckCommand**: Performs health checks and maintenance
  - Database connection verification
  - Configuration validation
  - API connectivity testing
  - Transaction statistics
  - Log cleanup

## Data Flow

1. **Initialization Flow**:
   - User inputs TPIN, Branch ID, and Device Serial via ConfigForm
   - ZraController validates input via ZraConfigRequest
   - ZraService sends initialization request to ZRA API
   - Device ID is stored in ZraConfig
   - Status is updated and displayed to the user

2. **Transaction Flow**:
   - Application calls ZraService methods or the Zra facade
   - Data validation occurs
   - Optional queueing for asynchronous processing
   - API request is made to ZRA
   - Response is logged and returned
   - Dashboard statistics are updated

3. **Monitoring Flow**:
   - Automatic logging of all transactions
   - Statistics calculation based on log data
   - Health checks via command or API endpoint
   - Dashboard widget displays key metrics

## Security Considerations

- No hardcoded credentials; all sensitive data stored in environment variables
- Input validation via Form Requests
- Sanitization of sensitive data in logs
- Configurable middleware for route protection
- Error handling prevents leakage of sensitive information

## Performance Optimization

- Queue-based processing for long-running operations
- Efficient database queries with proper indexing
- Minimal dependencies for lighter footprint
- Configurable timeout and retry settings

## Extension Points

The package is designed for extensibility:

- Configurable middleware
- Customizable routes
- Event broadcasting for important operations
- Extensible models with proper relationships
- Well-documented service methods for integration
