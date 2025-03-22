import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ConfigForm from '../../resources/js/Pages/ZraConfig/components/ConfigForm';
import '../setup';

// Mock the useForm hook from inertia
const mockUseForm = vi.fn();
const mockSetData = vi.fn();
const mockPost = vi.fn();
const mockReset = vi.fn();

// Mock the router
vi.mock('@inertiajs/react', () => ({
  useForm: () => ({
    data: {
      tpin: '',
      branch_id: '',
      device_serial: '',
    },
    setData: mockSetData,
    post: mockPost,
    processing: false,
    errors: {},
    reset: mockReset,
  }),
  router: {
    post: mockPost
  }
}));

// Mock the route function
vi.mock('@inertiajs/core', () => ({
  route: (name) => {
    if (name === 'zra.initialize') return '/zra/initialize';
    if (name === 'zra.test-sales') return '/zra/test-sales';
    return '/default-route';
  }
}));

describe('ConfigForm Component', () => {
  it('renders the initialization form when not initialized', async () => {
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

  it('submits the form with valid data', async () => {
    const props = {
      config: null,
      isInitialized: false,
    };

    render(<ConfigForm {...props} />);
    
    // Fill out the form
    const user = userEvent.setup();
    await user.type(screen.getByLabelText(/TPIN/i), '1234567890');
    await user.type(screen.getByLabelText(/Branch ID/i), '001');
    await user.type(screen.getByLabelText(/Device Serial Number/i), 'DEVICE123456');
    
    // Submit the form
    await user.click(screen.getByRole('button', { name: /Initialize Device/i }));
    
    // Verify router.post was called
    expect(mockPost).toHaveBeenCalled();
  });
});
