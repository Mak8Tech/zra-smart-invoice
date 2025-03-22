import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import StatusIndicator from '../../resources/js/Pages/ZraConfig/components/StatusIndicator';
import type { StatusIndicatorProps } from '../types';

describe('StatusIndicator Component', () => {
  it('displays initialized status correctly', () => {
    const props: StatusIndicatorProps = {
      isInitialized: true,
      environment: 'sandbox',
      status: {
        status: 'active',
        message: 'Device is active and ready',
        last_initialized: '2025-03-22 01:30:45',
      },
      lastSync: '2025-03-22 01:45:22',
    };

    render(<StatusIndicator {...props} />);
    
    expect(screen.getByText(/Device Status/i)).toBeInTheDocument();
    expect(screen.getByText(/Initialized/i)).toBeInTheDocument();
    expect(screen.getByText(/Sandbox/i)).toBeInTheDocument();
    expect(screen.getByText(/active/i)).toBeInTheDocument();
  });

  it('displays uninitialized status correctly', () => {
    const props: StatusIndicatorProps = {
      isInitialized: false,
      environment: 'sandbox',
      status: null,
      lastSync: null,
    };

    render(<StatusIndicator {...props} />);
    
    expect(screen.getByText(/Device Status/i)).toBeInTheDocument();
    expect(screen.getByText(/Not Initialized/i)).toBeInTheDocument();
    expect(screen.getByText(/Sandbox/i)).toBeInTheDocument();
  });

  it('displays production environment correctly', () => {
    const props: StatusIndicatorProps = {
      isInitialized: true,
      environment: 'production',
      status: {
        status: 'active',
        message: 'Device is active and ready',
        last_initialized: '2025-03-22 01:30:45',
      },
      lastSync: '2025-03-22 01:45:22',
    };

    render(<StatusIndicator {...props} />);
    
    expect(screen.getByText(/Production/i)).toBeInTheDocument();
  });
});
