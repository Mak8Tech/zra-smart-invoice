// resources/js/Pages/ZraConfig/components/ConfigForm.tsx
import React, { useState } from "react";
import { router } from "@inertiajs/react";

// Define the route function interface
declare function route(name: string, params?: Record<string, any>): string;

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

interface Props {
  config: ZraConfig | null;
  isInitialized: boolean;
}

export default function ConfigForm({ config, isInitialized }: Props) {
  const [tpin, setTpin] = useState(config?.tpin || "");
  const [branchId, setBranchId] = useState(config?.branch_id || "");
  const [deviceSerial, setDeviceSerial] = useState(config?.device_serial || "");
  const [loading, setLoading] = useState(false);
  const [testLoading, setTestLoading] = useState(false);
  const [testResult, setTestResult] = useState<any>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    router.post(
      route("zra.initialize"),
      {
        tpin,
        branch_id: branchId,
        device_serial: deviceSerial,
      },
      {
        onSuccess: () => {
          setLoading(false);
        },
        onError: () => {
          setLoading(false);
        },
      }
    );
  };

  const handleTestSales = () => {
    setTestLoading(true);
    setTestResult(null);

    router.post(
      route("zra.test-sales"),
      {},
      {
        onSuccess: (page: any) => {
          setTestLoading(false);
          // Type-safe access to page.props.flash.data
          if (page?.props?.flash?.data) {
            setTestResult(page.props.flash.data);
          }
        },
        onError: () => {
          setTestLoading(false);
          setTestResult({
            success: false,
            message: "An error occurred while testing sales submission",
          });
        },
      }
    );
  };

  return (
    <div>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label
            htmlFor="tpin"
            className="block text-sm font-medium text-gray-700"
          >
            TPIN (10 characters)
          </label>
          <input
            type="text"
            id="tpin"
            value={tpin}
            onChange={(e) => setTpin(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={10}
            required
          />
        </div>

        <div>
          <label
            htmlFor="branch_id"
            className="block text-sm font-medium text-gray-700"
          >
            Branch ID (3 characters)
          </label>
          <input
            type="text"
            id="branch_id"
            value={branchId}
            onChange={(e) => setBranchId(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={3}
            required
          />
        </div>

        <div>
          <label
            htmlFor="device_serial"
            className="block text-sm font-medium text-gray-700"
          >
            Device Serial Number
          </label>
          <input
            type="text"
            id="device_serial"
            value={deviceSerial}
            onChange={(e) => setDeviceSerial(e.target.value)}
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            maxLength={100}
            required
          />
        </div>

        <div className="flex space-x-4">
          <button
            type="submit"
            disabled={loading}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {loading ? "Initializing..." : "Initialize Device"}
          </button>

          {isInitialized && (
            <button
              type="button"
              onClick={handleTestSales}
              disabled={testLoading}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              {testLoading ? "Testing..." : "Test Sales Submission"}
            </button>
          )}
        </div>
      </form>

      {testResult && (
        <div
          className={`mt-6 p-4 rounded-md ${
            testResult.success ? "bg-green-50" : "bg-red-50"
          }`}
        >
          <div className="flex">
            <div className="flex-shrink-0">
              {testResult.success ? (
                <svg
                  className="h-5 w-5 text-green-400"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clipRule="evenodd"
                  />
                </svg>
              ) : (
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
              )}
            </div>
            <div className="ml-3">
              <h3
                className={`text-sm font-medium ${
                  testResult.success ? "text-green-800" : "text-red-800"
                }`}
              >
                {testResult.success ? "Test Successful" : "Test Failed"}
              </h3>
              <div
                className={`mt-2 text-sm ${
                  testResult.success ? "text-green-700" : "text-red-700"
                }`}
              >
                <p>{testResult.message}</p>
                {testResult.reference && (
                  <p className="mt-1">Reference: {testResult.reference}</p>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
