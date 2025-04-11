<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;

class ZraReportService
{
    /**
     * @var ZraService
     */
    protected $zraService;

    /**
     * @var ZraTaxService
     */
    protected $taxService;

    /**
     * Constructor
     *
     * @param ZraService $zraService
     * @param ZraTaxService $taxService
     */
    public function __construct(ZraService $zraService, ZraTaxService $taxService)
    {
        $this->zraService = $zraService;
        $this->taxService = $taxService;
    }

    /**
     * Generate a report based on type and date
     *
     * @param string $type The report type (x, z, daily, monthly)
     * @param Carbon $date The date for the report
     * @return array The generated report data
     * @throws Exception
     */
    public function generateReport(string $type, Carbon $date): array
    {
        $config = ZraConfig::getActive();
        if (!$config) {
            throw new Exception('No active ZRA configuration found. Please initialize the device first.');
        }

        switch ($type) {
            case 'x':
                return $this->generateXReport($date);
            case 'z':
                return $this->generateZReport($date);
            case 'daily':
                return $this->generateDailyReport($date);
            case 'monthly':
                return $this->generateMonthlyReport($date);
            default:
                throw new Exception("Invalid report type: {$type}");
        }
    }

    /**
     * Generate an X report (interim report for current day)
     *
     * @param Carbon $date
     * @return array
     */
    protected function generateXReport(Carbon $date): array
    {
        // X reports show all transactions for the current day without finalizing
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        $transactions = ZraTransactionLog::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = $this->calculateTransactionSummary($transactions);
        $taxSummary = $this->calculateTaxSummary($transactions);

        return [
            'type' => 'x',
            'date' => $date->format('Y-m-d'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'device_info' => $this->getDeviceInfo(),
            'status' => 'Interim (Not Finalized)',
            'transaction_summary' => $summary,
            'tax_summary' => $taxSummary,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'transaction_type' => $transaction->transaction_type,
                    'status' => $transaction->status,
                    'total_amount' => $transaction->getData('total_amount') ?? 0,
                    'total_tax' => $transaction->getData('total_tax') ?? 0,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate a Z report (finalized report for a day)
     *
     * @param Carbon $date
     * @return array
     */
    protected function generateZReport(Carbon $date): array
    {
        // Z reports are similar to X reports but mark the day as finalized
        $report = $this->generateXReport($date);

        // Mark as finalized
        $report['status'] = 'Finalized';
        $report['type'] = 'z';

        // Add finalization details
        $report['finalized_at'] = now()->format('Y-m-d H:i:s');
        $report['finalized_by'] = auth()->user() ? auth()->user()->email : 'system';

        // Store report in database or mark as finalized
        // This is a placeholder for actual implementation in a real system
        // which would typically mark the day as "closed" in a financial sense

        return $report;
    }

    /**
     * Generate a daily report
     *
     * @param Carbon $date
     * @return array
     */
    protected function generateDailyReport(Carbon $date): array
    {
        // Daily reports are the same as Z reports but might have different formatting
        return $this->generateZReport($date);
    }

    /**
     * Generate a monthly report
     *
     * @param Carbon $date
     * @return array
     */
    protected function generateMonthlyReport(Carbon $date): array
    {
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $transactions = ZraTransactionLog::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = $this->calculateTransactionSummary($transactions);
        $taxSummary = $this->calculateTaxSummary($transactions);

        // Calculate daily totals
        $dailyTotals = [];
        foreach (
            $transactions->groupBy(function ($transaction) {
                return $transaction->created_at->format('Y-m-d');
            }) as $day => $dayTransactions
        ) {
            $daySummary = $this->calculateTransactionSummary($dayTransactions);
            $totalAmount = array_sum(array_column($daySummary, 'amount'));
            $totalTax = array_sum(array_column($daySummary, 'tax'));

            $dailyTotals[$day] = [
                'count' => $dayTransactions->count(),
                'amount' => $totalAmount,
                'tax' => $totalTax,
            ];
        }

        return [
            'type' => 'monthly',
            'date' => $date->format('Y-m'),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'device_info' => $this->getDeviceInfo(),
            'status' => 'Monthly Summary',
            'transaction_summary' => $summary,
            'tax_summary' => $taxSummary,
            'daily_totals' => $dailyTotals,
        ];
    }

    /**
     * Calculate transaction summary
     *
     * @param \Illuminate\Support\Collection $transactions
     * @return array
     */
    protected function calculateTransactionSummary($transactions): array
    {
        $summary = [];

        // Group by transaction type
        foreach ($transactions->groupBy('transaction_type') as $type => $typeTransactions) {
            $totalAmount = 0;
            $totalTax = 0;

            foreach ($typeTransactions as $transaction) {
                $totalAmount += $transaction->getData('total_amount') ?? 0;
                $totalTax += $transaction->getData('total_tax') ?? 0;
            }

            $summary[$type] = [
                'count' => $typeTransactions->count(),
                'amount' => $totalAmount,
                'tax' => $totalTax,
            ];
        }

        return $summary;
    }

    /**
     * Calculate tax summary
     *
     * @param \Illuminate\Support\Collection $transactions
     * @return array
     */
    protected function calculateTaxSummary($transactions): array
    {
        $summary = [];
        $taxCategories = config('zra.tax_categories', []);

        // Initialize tax categories with zero amounts
        foreach ($taxCategories as $code => $category) {
            $summary[$code] = [
                'name' => $category['name'],
                'rate' => $category['default_rate'],
                'amount' => 0,
            ];
        }

        // Sum up tax amounts by category
        foreach ($transactions as $transaction) {
            $taxSummary = $transaction->getData('tax_summary');

            if ($taxSummary && is_array($taxSummary)) {
                foreach ($taxSummary as $code => $taxInfo) {
                    if (isset($summary[$code])) {
                        $summary[$code]['amount'] += $taxInfo['tax_amount'] ?? 0;
                    }
                }
            }
        }

        // Remove any categories with zero amount
        return array_filter($summary, function ($item) {
            return $item['amount'] > 0;
        });
    }

    /**
     * Get device information
     *
     * @return array
     */
    protected function getDeviceInfo(): array
    {
        $config = ZraConfig::getActive();

        return [
            'tpin' => $config->tpin,
            'branch_id' => $config->branch_id,
            'device_serial' => $config->device_serial,
            'environment' => $config->environment,
            'last_initialized_at' => $config->last_initialized_at ? $config->last_initialized_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Save report to storage
     *
     * @param array $report
     * @param string $type
     * @param Carbon $date
     * @param string $format
     * @return string The file path
     */
    public function saveReport(array $report, string $type, Carbon $date, string $format): string
    {
        $directory = 'zra/reports/' . $type;
        $filename = $date->format('Y-m-d') . '_' . $type . '_report';

        // Create directory if it doesn't exist
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Save based on format
        switch ($format) {
            case 'json':
                $path = $directory . '/' . $filename . '.json';
                Storage::put($path, json_encode($report, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $path = $directory . '/' . $filename . '.csv';
                $this->saveCsv($report, $path);
                break;
            case 'pdf':
                $path = $directory . '/' . $filename . '.pdf';
                $this->savePdf($report, $path);
                break;
            default:
                $path = $directory . '/' . $filename . '.txt';
                Storage::put($path, $this->formatReportAsText($report, $type));
                break;
        }

        return $path;
    }

    /**
     * Email report
     *
     * @param array $report
     * @param string $type
     * @param Carbon $date
     * @param string $email
     * @return void
     */
    public function emailReport(array $report, string $type, Carbon $date, string $email): void
    {
        // Save report as attachment
        $path = $this->saveReport($report, $type, $date, 'pdf');

        // Email implementation would go here
        // This is a placeholder for the actual implementation
        Log::info("Report would be emailed to {$email} with attachment {$path}");
    }

    /**
     * Format report as text
     *
     * @param array $report
     * @param string $type
     * @return string
     */
    protected function formatReportAsText(array $report, string $type): string
    {
        // Simple text formatting for reports
        $text = "=== ZRA {$type} Report ===\n";
        $text .= "Date: {$report['date']}\n";
        $text .= "Generated: {$report['generated_at']}\n";
        $text .= "Device: {$report['device_info']['device_serial']}\n";
        $text .= "Status: {$report['status']}\n\n";

        $text .= "Transaction Summary:\n";
        foreach ($report['transaction_summary'] as $type => $data) {
            $text .= sprintf(
                "- %s: %d transactions, %.2f amount, %.2f tax\n",
                $type,
                $data['count'],
                $data['amount'],
                $data['tax']
            );
        }

        $text .= "\nTax Summary:\n";
        foreach ($report['tax_summary'] as $category => $data) {
            $text .= sprintf(
                "- %s (%.2f%%): %.2f\n",
                $data['name'],
                $data['rate'],
                $data['amount']
            );
        }

        return $text;
    }

    /**
     * Save report as CSV
     * Placeholder implementation
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    protected function saveCsv(array $report, string $path): void
    {
        // CSV implementation would go here
        Storage::put($path, "CSV format not implemented yet");
    }

    /**
     * Save report as PDF
     * Placeholder implementation
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    protected function savePdf(array $report, string $path): void
    {
        // PDF implementation would go here
        Storage::put($path, "PDF format not implemented yet");
    }
}
