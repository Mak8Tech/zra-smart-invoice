import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import TransactionLog from '../../resources/js/Pages/ZraConfig/components/TransactionLog';

describe('TransactionLog Component', () => {
  const mockLogs = [
    {
      id: 1,
      transaction_type: 'sales_data',
      reference: 'INV-12345',
      status: 'success',
      error_message: null,
      created_at: '2025-03-22 02:15:30',
    },
    {
      id: 2,
      transaction_type: 'purchase_data',
      reference: 'PO-67890',
      status: 'failed',
      error_message: 'Connection timeout',
      created_at: '2025-03-22 02:30:45',
    },
    {
      id: 3,
      transaction_type: 'stock_data',
      reference: 'STK-11223',
      status: 'success',
      error_message: null,
      created_at: '2025-03-22 03:10:22',
    },
  ];

  it('renders transaction logs correctly', () => {
    render(<TransactionLog logs={mockLogs} />);
    
    expect(screen.getByText(/Transaction Logs/i)).toBeInTheDocument();
    expect(screen.getByText(/INV-12345/i)).toBeInTheDocument();
    expect(screen.getByText(/PO-67890/i)).toBeInTheDocument();
    expect(screen.getByText(/STK-11223/i)).toBeInTheDocument();
    
    // Check status indicators
    expect(screen.getAllByText(/success/i)).toHaveLength(2);
    expect(screen.getByText(/failed/i)).toBeInTheDocument();
    
    // Check error message
    expect(screen.getByText(/Connection timeout/i)).toBeInTheDocument();
  });

  it('displays empty state when no logs exist', () => {
    render(<TransactionLog logs={[]} />);
    
    expect(screen.getByText(/No transaction logs found/i)).toBeInTheDocument();
  });

  it('formats transaction types correctly', () => {
    render(<TransactionLog logs={mockLogs} />);
    
    expect(screen.getByText(/Sales Data/i)).toBeInTheDocument();
    expect(screen.getByText(/Purchase Data/i)).toBeInTheDocument();
    expect(screen.getByText(/Stock Data/i)).toBeInTheDocument();
  });
});
