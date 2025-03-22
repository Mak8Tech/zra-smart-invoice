// resources/js/Pages/ZraConfig/components/DashboardWidget.tsx
import React from "react";

interface Stats {
  total_transactions: number;
  successful_transactions: number;
  failed_transactions: number;
  success_rate: number;
  last_transaction_date: string | null;
}

interface Props {
  stats: Stats;
  isInitialized: boolean;
}

export default function DashboardWidget({ stats, isInitialized }: Props) {
  return (
    <div className="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200">
      <div className="px-4 py-5 sm:px-6">
        <h3 className="text-lg leading-6 font-medium text-gray-900">
          ZRA Smart Invoice Status
        </h3>
      </div>
      <div className="px-4 py-5 sm:p-6">
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
          <div className="bg-gray-50 overflow-hidden shadow rounded-md">
            <div className="px-4 py-5 sm:p-6">
              <dt className="text-sm font-medium text-gray-500 truncate">
                Total Transactions
              </dt>
              <dd className="mt-1 text-3xl font-semibold text-gray-900">
                {stats.total_transactions}
              </dd>
            </div>
          </div>

          <div className="bg-green-50 overflow-hidden shadow rounded-md">
            <div className="px-4 py-5 sm:p-6">
              <dt className="text-sm font-medium text-gray-500 truncate">
                Success Rate
              </dt>
              <dd className="mt-1 text-3xl font-semibold text-gray-900">
                {stats.success_rate}%
              </dd>
            </div>
          </div>

          <div className="bg-blue-50 overflow-hidden shadow rounded-md">
            <div className="px-4 py-5 sm:p-6">
              <dt className="text-sm font-medium text-gray-500 truncate">
                Device Status
              </dt>
              <dd className="mt-1 text-3xl font-semibold text-gray-900">
                {isInitialized ? (
                  <span className="text-green-600">Active</span>
                ) : (
                  <span className="text-red-600">Inactive</span>
                )}
              </dd>
            </div>
          </div>
        </div>

        {stats.last_transaction_date && (
          <div className="mt-5 text-sm text-gray-500">
            Last transaction: {stats.last_transaction_date}
          </div>
        )}

        {!isInitialized && (
          <div className="mt-5">
            <div className="rounded-md bg-yellow-50 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <svg
                    className="h-5 w-5 text-yellow-400"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
                <div className="ml-3">
                  <h3 className="text-sm font-medium text-yellow-800">
                    Device Not Initialized
                  </h3>
                  <div className="mt-2 text-sm text-yellow-700">
                    <p>
                      Your ZRA device is not initialized. Please go to the
                      configuration section to set up your device.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
