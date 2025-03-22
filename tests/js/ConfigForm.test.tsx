import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import '../setup';

// Use vi.hoisted to define variables that need to be available during hoisting
const mockPostFn = vi.hoisted(() => vi.fn());
const mockRouteFn = vi.hoisted(() => vi.fn((name) => {
  if (name === 'zra.initialize') return '/zra/initialize';
  if (name === 'zra.test-sales') return '/zra/test-sales';
  return '/default-route';
}));

// Create a global route function
// This has to be done before any imports that use it
vi.stubGlobal('route', mockRouteFn);

// Mock modules
vi.mock('@inertiajs/react', () => ({
  router: {
    post: mockPostFn
  },
  useForm: vi.fn().mockReturnValue({
    data: {
      tpin: '',
      branch_id: '',
      device_serial: '',
    },
    setData: vi.fn(),
    post: vi.fn(),
    processing: false,
    errors: {},
    reset: vi.fn(),
  })
}));

// Import the component after mocks are set up
import ConfigForm from '../../resources/js/Pages/ZraConfig/components/ConfigForm';

describe('ConfigForm Component', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the initialization form when not initialized', () => {
    const props = {
      config: null,
      isInitialized: false,
    };

    render(<ConfigForm {...props} />);
    
    expect(screen.getByText('Initialize Device')).toBeInTheDocument();
    expect(screen.getByLabelText(/TPIN/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Branch ID/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Device Serial Number/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Initialize Device/i })).toBeInTheDocument();
  });

  it('displays config details when already initialized', () => {
    const props = {
      config: {
        id: 1,
        tpin: '1234567890',
        branch_id: '001',
        device_serial: 'DEVICE123456',
        environment: 'sandbox',
        status: {
          status: 'active',
          message: 'Device is active and ready',
        },
        last_initialized_at: '2025-03-22 01:30:45',
        last_sync_at: '2025-03-22 01:45:22',
      },
      isInitialized: true,
    };

    render(<ConfigForm {...props} />);
    
    // Verify form shows the configured values
    const tpinInput = screen.getByLabelText(/TPIN/i) as HTMLInputElement;
    const branchIdInput = screen.getByLabelText(/Branch ID/i) as HTMLInputElement;
    const deviceSerialInput = screen.getByLabelText(/Device Serial/i) as HTMLInputElement;
    
    expect(tpinInput.value).toBe('1234567890');
    expect(branchIdInput.value).toBe('001');
    expect(deviceSerialInput.value).toBe('DEVICE123456');
    expect(screen.getByRole('button', { name: /Test Sales Submission/i })).toBeInTheDocument();
  });

  it('submits the form with valid data', () => {
    const props = {
      config: null,
      isInitialized: false,
    };

    render(<ConfigForm {...props} />);
    
    // Fill out the form fields
    fireEvent.change(screen.getByLabelText(/TPIN/i), { target: { value: '1234567890' } });
    fireEvent.change(screen.getByLabelText(/Branch ID/i), { target: { value: '001' } });
    fireEvent.change(screen.getByLabelText(/Device Serial Number/i), { target: { value: 'DEVICE123456' } });
    
    // Find and submit the form
    const submitButton = screen.getByRole('button', { name: /Initialize Device/i });
    const form = submitButton.closest('form');
    
    // Check if form element exists to fix TypeScript null error
    if (form) {
      fireEvent.submit(form);
    } else {
      // If we can't find the form, submit via the button click as fallback
      fireEvent.click(submitButton);
    }
    
    // Verify route and post were called
    expect(mockRouteFn).toHaveBeenCalledWith('zra.initialize');
    expect(mockPostFn).toHaveBeenCalled();
  });
});
