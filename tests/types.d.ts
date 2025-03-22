// Import React types
import React from 'react';

// Define StatusIndicator prop types
interface Status {
  status: string;
  message: string;
  last_initialized?: string;
}

interface StatusIndicatorProps {
  isInitialized: boolean;
  environment: string;
  status?: Status | null;
  lastSync?: string | null;
}

// Define DashboardWidget prop types
interface Stats {
  total_transactions: number;
  successful_transactions: number;
  failed_transactions: number;
  success_rate: number;
  last_transaction_date: string | null;
}

interface DashboardWidgetProps {
  stats: Stats;
  isInitialized: boolean;
}

// Define ConfigForm prop types
interface Config {
  id: number;
  tpin: string;
  branch_id: string;
  device_serial: string;
  environment: string;
  status: Status;
  last_initialized_at: string | null;
  last_sync_at: string | null;
}

interface ConfigFormProps {
  config: Config | null;
  isInitialized: boolean;
}

// Define TransactionLog prop types
interface Log {
  id: number;
  transaction_type: string;
  reference: string;
  status: string;
  error_message: string | null;
  created_at: string;
}

interface TransactionLogProps {
  logs: Log[];
}

declare module "*.tsx" {
  const component: React.FC;
  export default component;
}

// Declare global types for Vitest
declare global {
  namespace Vi {
    interface Assertion {
      toBeInTheDocument(): void;
    }
  }
}
