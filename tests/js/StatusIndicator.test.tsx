import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import StatusIndicator from '../../resources/js/Pages/ZraConfig/components/StatusIndicator';
import '../setup';

interface Status {
  status: string;
  message: string;
  last_initialized?: string;
}

interface StatusIndicatorProps {
  isInitialized: boolean;
  environment: string;
  status?: Status | undefined;
  lastSync: string | null;
}

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
    
    expect(screen.getByText(/Status:/)).toBeInTheDocument();
    // Use a more specific selector to avoid ambiguity
    expect(screen.getByText(/^Initialized$/)).toBeInTheDocument();
    expect(screen.getByText('Sandbox (Test)')).toBeInTheDocument();
    expect(screen.getByText(/Last Initialized:/)).toBeInTheDocument();
    expect(screen.getByText(/Last Sync:/)).toBeInTheDocument();
    expect(screen.getByText('Ready')).toBeInTheDocument();
  });

  it('displays uninitialized status correctly', () => {
    const props: StatusIndicatorProps = {
      isInitialized: false,
      environment: 'sandbox',
      status: undefined,
      lastSync: null,
    };

    render(<StatusIndicator {...props} />);
    
    expect(screen.getByText(/Status:/)).toBeInTheDocument();
    expect(screen.getByText('Not Initialized')).toBeInTheDocument();
    expect(screen.getByText('Sandbox (Test)')).toBeInTheDocument();
    expect(screen.getByText('Needs Setup')).toBeInTheDocument();
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
    
    expect(screen.getByText('Production')).toBeInTheDocument();
  });
});
