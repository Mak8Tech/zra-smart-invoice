import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import DashboardWidget from '../../resources/js/Pages/ZraConfig/components/DashboardWidget';
import '../setup';

describe('DashboardWidget Component', () => {
  it('renders the dashboard with statistics when initialized', () => {
    const stats = {
      total_transactions: 150,
      successful_transactions: 142,
      failed_transactions: 8,
      success_rate: 94.7,
      last_transaction_date: '2025-03-22 03:15:22',
    };

    render(<DashboardWidget stats={stats} isInitialized={true} />);
    
    expect(screen.getByText('ZRA Smart Invoice Status')).toBeInTheDocument();
    expect(screen.getByText('Total Transactions')).toBeInTheDocument();
    expect(screen.getByText('150')).toBeInTheDocument(); // Total transactions
    expect(screen.getByText('94.7%')).toBeInTheDocument(); // Success rate
    expect(screen.getByText('Active')).toBeInTheDocument(); // Device status
    expect(screen.getByText(/Last transaction:/)).toBeInTheDocument();
  });

  it('displays initialization message when not initialized', () => {
    const stats = {
      total_transactions: 0,
      successful_transactions: 0,
      failed_transactions: 0,
      success_rate: 0,
      last_transaction_date: null,
    };

    render(<DashboardWidget stats={stats} isInitialized={false} />);
    
    expect(screen.getByText('Device Not Initialized')).toBeInTheDocument();
    expect(screen.getByText(/Your ZRA device is not initialized/)).toBeInTheDocument();
    expect(screen.getByText('Inactive')).toBeInTheDocument();
  });

  it('displays empty state when no transactions exist', () => {
    const stats = {
      total_transactions: 0,
      successful_transactions: 0,
      failed_transactions: 0,
      success_rate: 0,
      last_transaction_date: null,
    };

    render(<DashboardWidget stats={stats} isInitialized={true} />);
    
    expect(screen.getByText('0')).toBeInTheDocument(); // No transactions
    expect(screen.getByText('0%')).toBeInTheDocument(); // Zero success rate
    expect(screen.getByText('Active')).toBeInTheDocument(); // Still active
  });
});
