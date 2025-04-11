// resources/js/Pages/ZraConfig/components/DashboardWidget.tsx
import React, { useState } from "react";
import { router } from "@inertiajs/react";

// Define the route function interface
declare function route(name: string, params?: Record<string, any>): string;

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
  const [reportType, setReportType] = useState<string>("x");
  const [reportLoading, setReportLoading] = useState<boolean>(false);
  const [reportError, setReportError] = useState<string | null>(null);

  const generateReport = (type: string) => {
    setReportLoading(true);
    setReportError(null);
    setReportType(type);

    router.post(
      route("zra.generate-report"),
      { type },
      {
        onSuccess: () => {
          setReportLoading(false);
        },
        onError: (errors) => {
          setReportLoading(false);
          setReportError(errors.message || "Failed to generate report");
        },
      }
    );
  };

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

        {isInitialized && (
          <div className="mt-8">
            <h4 className="text-lg font-medium text-gray-900 mb-4">Reports</h4>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <div className="bg-indigo-50 overflow-hidden shadow rounded-md">
                <div className="px-4 py-5 sm:p-6">
                  <h5 className="text-md font-medium text-gray-900">
                    X Report (Interim)
                  </h5>
                  <p className="mt-1 text-sm text-gray-500">
                    Generate an X report showing current day transactions
                    without finalizing.
                  </p>
                  <div className="mt-4">
                    <button
                      type="button"
                      onClick={() => generateReport("x")}
                      disabled={reportLoading && reportType === "x"}
                      className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                      {reportLoading && reportType === "x"
                        ? "Generating..."
                        : "Generate X Report"}
                    </button>
                  </div>
                </div>
              </div>

              <div className="bg-purple-50 overflow-hidden shadow rounded-md">
                <div className="px-4 py-5 sm:p-6">
                  <h5 className="text-md font-medium text-gray-900">
                    Z Report (Finalized)
                  </h5>
                  <p className="mt-1 text-sm text-gray-500">
                    Generate a Z report to finalize the day's transactions for
                    auditing.
                  </p>
                  <div className="mt-4">
                    <button
                      type="button"
                      onClick={() => generateReport("z")}
                      disabled={reportLoading && reportType === "z"}
                      className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50"
                    >
                      {reportLoading && reportType === "z"
                        ? "Generating..."
                        : "Generate Z Report"}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-4">
              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div className="bg-teal-50 overflow-hidden shadow rounded-md">
                  <div className="px-4 py-5 sm:p-6">
                    <h5 className="text-md font-medium text-gray-900">
                      Daily Report
                    </h5>
                    <p className="mt-1 text-sm text-gray-500">
                      Generate a comprehensive daily report for any date.
                    </p>
                    <div className="mt-4">
                      <button
                        type="button"
                        onClick={() => generateReport("daily")}
                        disabled={reportLoading && reportType === "daily"}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 disabled:opacity-50"
                      >
                        {reportLoading && reportType === "daily"
                          ? "Generating..."
                          : "Generate Daily Report"}
                      </button>
                    </div>
                  </div>
                </div>

                <div className="bg-amber-50 overflow-hidden shadow rounded-md">
                  <div className="px-4 py-5 sm:p-6">
                    <h5 className="text-md font-medium text-gray-900">
                      Monthly Report
                    </h5>
                    <p className="mt-1 text-sm text-gray-500">
                      Generate a monthly summary report for auditing purposes.
                    </p>
                    <div className="mt-4">
                      <button
                        type="button"
                        onClick={() => generateReport("monthly")}
                        disabled={reportLoading && reportType === "monthly"}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50"
                      >
                        {reportLoading && reportType === "monthly"
                          ? "Generating..."
                          : "Generate Monthly Report"}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {reportError && (
              <div className="mt-4 rounded-md bg-red-50 p-4">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <svg
                      className="h-5 w-5 text-red-400"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path
                        fillRule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clipRule="evenodd"
                      />
                    </svg>
                  </div>
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">
                      Error generating report
                    </h3>
                    <div className="mt-2 text-sm text-red-700">
                      <p>{reportError}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
