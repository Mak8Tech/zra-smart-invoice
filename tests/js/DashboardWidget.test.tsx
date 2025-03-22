import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import DashboardWidget from '../../resources/js/Pages/ZraConfig/components/DashboardWidget';

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
    
    expect(screen.getByText(/Dashboard/i)).toBeInTheDocument();
    expect(screen.getByText(/150/i)).toBeInTheDocument(); // Total transactions
    expect(screen.getByText(/142/i)).toBeInTheDocument(); // Successful transactions
    expect(screen.getByText(/8/i)).toBeInTheDocument();   // Failed transactions
    expect(screen.getByText(/94.7%/i)).toBeInTheDocument(); // Success rate
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
    
    expect(screen.getByText(/Your device is not initialized/i)).toBeInTheDocument();
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
    
    expect(screen.getByText(/No transactions recorded yet/i)).toBeInTheDocument();
  });
});
