import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import TransactionLog from '../../resources/js/Pages/ZraConfig/components/TransactionLog';
import '../setup';

describe('TransactionLog Component', () => {
  const mockLogs = [
    {
      id: 1,
      transaction_type: 'sales',
      reference: 'INV-12345',
      status: 'success',
      error_message: null,
      created_at: '2025-03-22 02:15:30',
    },
    {
      id: 2,
      transaction_type: 'purchase',
      reference: 'PO-67890',
      status: 'failed',
      error_message: 'Connection timeout',
      created_at: '2025-03-22 02:30:45',
    },
    {
      id: 3,
      transaction_type: 'stock',
      reference: 'STK-11223',
      status: 'success',
      error_message: null,
      created_at: '2025-03-22 03:10:22',
    },
  ];

  it('renders transaction logs correctly', () => {
    render(<TransactionLog logs={mockLogs} />);
    
    expect(screen.getByText('Recent Transaction Logs')).toBeInTheDocument();
    expect(screen.getByText('INV-12345')).toBeInTheDocument();
    expect(screen.getByText('PO-67890')).toBeInTheDocument();
    expect(screen.getByText('STK-11223')).toBeInTheDocument();
    
    // Check status indicators
    expect(screen.getAllByText('success')).toHaveLength(2);
    expect(screen.getByText('failed')).toBeInTheDocument();
    
    // Check error message
    expect(screen.getByText('Connection timeout')).toBeInTheDocument();
  });

  it('displays empty state when no logs exist', () => {
    render(<TransactionLog logs={[]} />);
    
    expect(screen.getByText('No transaction logs found.')).toBeInTheDocument();
  });

  it('formats transaction types correctly', () => {
    render(<TransactionLog logs={mockLogs} />);
    
    expect(screen.getByText('Sales')).toBeInTheDocument();
    expect(screen.getByText('Purchase')).toBeInTheDocument();
    expect(screen.getByText('Stock')).toBeInTheDocument();
  });
});
