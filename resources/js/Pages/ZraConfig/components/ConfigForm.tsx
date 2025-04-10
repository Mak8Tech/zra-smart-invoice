// resources/js/Pages/ZraConfig/components/ConfigForm.tsx
import React, { useState, useEffect } from "react";
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

interface InvoiceTypeOption {
  value: string;
  label: string;
}

interface TransactionTypeOption {
  value: string;
  label: string;
}

interface TaxCategoryOption {
  code: string;
  name: string;
  rate: number;
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

  // State for invoice and transaction types
  const [invoiceType, setInvoiceType] = useState<string>("NORMAL");
  const [transactionType, setTransactionType] = useState<string>("SALE");
  const [invoiceTypes, setInvoiceTypes] = useState<InvoiceTypeOption[]>([]);
  const [transactionTypes, setTransactionTypes] = useState<
    TransactionTypeOption[]
  >([]);

  // State for tax categories
  const [taxCategory, setTaxCategory] = useState<string>("VAT");
  const [taxCategories, setTaxCategories] = useState<TaxCategoryOption[]>([]);

  // Fetch invoice and transaction types on component mount
  useEffect(() => {
    // This would normally come from an API call, but we're using hardcoded values for now
    // In a real application, you would fetch this from the backend
    setInvoiceTypes([
      { value: "NORMAL", label: "Normal Invoice" },
      { value: "COPY", label: "Copy of Invoice" },
      { value: "TRAINING", label: "Training Invoice" },
      { value: "PROFORMA", label: "Proforma Invoice" },
    ]);

    setTransactionTypes([
      { value: "SALE", label: "Sale" },
      { value: "CREDIT_NOTE", label: "Credit Note" },
      { value: "DEBIT_NOTE", label: "Debit Note" },
      { value: "ADJUSTMENT", label: "Adjustment" },
      { value: "REFUND", label: "Refund" },
    ]);

    // Set tax categories
    setTaxCategories([
      { code: "VAT", name: "Value Added Tax", rate: 16.0 },
      { code: "TOURISM_LEVY", name: "Tourism Levy", rate: 1.5 },
      { code: "EXCISE", name: "Excise Duty", rate: 10.0 },
      { code: "ZERO_RATED", name: "Zero Rated", rate: 0.0 },
      { code: "EXEMPT", name: "Tax Exempt", rate: 0.0 },
    ]);
  }, []);

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
      {
        invoice_type: invoiceType,
        transaction_type: transactionType,
        tax_category: taxCategory,
      },
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

        {isInitialized && (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label
                  htmlFor="invoice_type"
                  className="block text-sm font-medium text-gray-700"
                >
                  Invoice Type
                </label>
                <select
                  id="invoice_type"
                  value={invoiceType}
                  onChange={(e) => setInvoiceType(e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  {invoiceTypes.map((type) => (
                    <option key={type.value} value={type.value}>
                      {type.label}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label
                  htmlFor="transaction_type"
                  className="block text-sm font-medium text-gray-700"
                >
                  Transaction Type
                </label>
                <select
                  id="transaction_type"
                  value={transactionType}
                  onChange={(e) => setTransactionType(e.target.value)}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                  {transactionTypes.map((type) => (
                    <option key={type.value} value={type.value}>
                      {type.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="mt-6">
              <label
                htmlFor="tax_category"
                className="block text-sm font-medium text-gray-700"
              >
                Default Tax Category
              </label>
              <select
                id="tax_category"
                value={taxCategory}
                onChange={(e) => setTaxCategory(e.target.value)}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                {taxCategories.map((category) => (
                  <option key={category.code} value={category.code}>
                    {category.name} ({category.rate}%)
                  </option>
                ))}
              </select>
              <p className="mt-1 text-sm text-gray-500">
                This tax category will be applied to all items by default. Items
                can have different tax categories in the actual API calls.
              </p>
            </div>
          </>
        )}

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
                {testResult.success ? "Success" : "Error"}
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

                {testResult.tax_details &&
                  Object.keys(testResult.tax_details).length > 0 && (
                    <div className="mt-4">
                      <h4 className="font-medium mb-2">Tax Summary:</h4>
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th
                              scope="col"
                              className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                              Category
                            </th>
                            <th
                              scope="col"
                              className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                              Rate
                            </th>
                            <th
                              scope="col"
                              className="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                            >
                              Amount
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {Object.entries(testResult.tax_details).map(
                            ([key, value]: [string, any]) => (
                              <tr key={key}>
                                <td className="px-3 py-2 whitespace-nowrap text-xs">
                                  {value.name}
                                </td>
                                <td className="px-3 py-2 whitespace-nowrap text-xs">
                                  {value.tax_rate}%
                                </td>
                                <td className="px-3 py-2 whitespace-nowrap text-xs">
                                  {value.tax_amount.toFixed(2)}
                                </td>
                              </tr>
                            )
                          )}
                        </tbody>
                      </table>
                    </div>
                  )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
