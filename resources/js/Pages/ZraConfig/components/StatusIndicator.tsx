// resources/js/Pages/ZraConfig/components/StatusIndicator.tsx
import React from "react";

interface Status {
  status: string;
  message: string;
  last_initialized?: string;
}

interface Props {
  isInitialized: boolean;
  environment: string;
  status?: Status;
  lastSync: string | null;
}

export default function StatusIndicator({
  isInitialized,
  environment,
  status,
  lastSync,
}: Props) {
  return (
    <div className="rounded-md bg-gray-50 p-4">
      <div className="flex">
        <div className="flex-shrink-0">
          {isInitialized ? (
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
          )}
        </div>
        <div className="ml-3 flex-1 md:flex md:justify-between">
          <div>
            <p className="text-sm text-gray-700">
              <span className="font-medium">Status:</span>{" "}
              {isInitialized ? "Initialized" : "Not Initialized"}
            </p>
            <p className="mt-1 text-sm text-gray-700">
              <span className="font-medium">Environment:</span>{" "}
              {environment === "sandbox" ? "Sandbox (Test)" : "Production"}
            </p>
            {status?.last_initialized && (
              <p className="mt-1 text-sm text-gray-700">
                <span className="font-medium">Last Initialized:</span>{" "}
                {status.last_initialized}
              </p>
            )}
            {lastSync && (
              <p className="mt-1 text-sm text-gray-700">
                <span className="font-medium">Last Sync:</span> {lastSync}
              </p>
            )}
          </div>
          <div className="mt-3 md:mt-0 md:ml-6">
            {isInitialized ? (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800">
                Ready
              </span>
            ) : (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800">
                Needs Setup
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
