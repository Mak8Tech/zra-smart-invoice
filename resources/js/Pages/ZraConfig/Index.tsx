// resources/js/Pages/ZraConfig/Index.tsx
import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import ConfigForm from "./components/ConfigForm";
import StatusIndicator from "./components/StatusIndicator";
import TransactionLog from "./components/TransactionLog";
import DashboardWidget from "./components/DashboardWidget";

interface ZraConfig {
  id: number;
  tpin: string;
  branch_id: string;
  device_serial: string;
  environment: string;
  status: {
    status: string;
    message: string;
    last_initialized?: string;
  };
  last_initialized_at: string | null;
  last_sync_at: string | null;
}

interface Log {
  id: number;
  transaction_type: string;
  reference: string;
  status: string;
  error_message: string | null;
  created_at: string;
}

interface Stats {
  total_transactions: number;
  successful_transactions: number;
  failed_transactions: number;
  success_rate: number;
  last_transaction_date: string | null;
}

interface Props {
  config: ZraConfig | null;
  logs: Log[];
  is_initialized: boolean;
  environment: string;
  stats: Stats;
}

export default function Index({
  config,
  logs,
  is_initialized,
  environment,
  stats,
}: Props) {
  const [activeTab, setActiveTab] = useState<"dashboard" | "config" | "logs">("dashboard");

  return (
    <>
      <Head title="ZRA Smart Invoice Configuration" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6 bg-white border-b border-gray-200">
              <h1 className="text-2xl font-semibold text-gray-900 mb-6">
                ZRA Smart Invoice Integration
              </h1>

              <StatusIndicator
                isInitialized={is_initialized}
                environment={environment}
                status={config?.status}
                lastSync={config?.last_sync_at}
              />

              <div className="mt-6 border-b border-gray-200 mb-6">
                <nav className="-mb-px flex space-x-8">
                  <button
                    onClick={() => setActiveTab("dashboard")}
                    className={`${
                      activeTab === "dashboard"
                        ? "border-indigo-500 text-indigo-600"
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                  >
                    Dashboard
                  </button>
                  <button
                    onClick={() => setActiveTab("config")}
                    className={`${
                      activeTab === "config"
                        ? "border-indigo-500 text-indigo-600"
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                  >
                    Configuration
                  </button>
                  <button
                    onClick={() => setActiveTab("logs")}
                    className={`${
                      activeTab === "logs"
                        ? "border-indigo-500 text-indigo-600"
                        : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                  >
                    Transaction Logs
                  </button>
                </nav>
              </div>

              {activeTab === "dashboard" && (
                <DashboardWidget stats={stats} isInitialized={is_initialized} />
              )}

              {activeTab === "config" && (
                <ConfigForm config={config} isInitialized={is_initialized} />
              )}

              {activeTab === "logs" && (
                <TransactionLog logs={logs} />
              )}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
