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

        // Get user info safely without using auth() helper
        $report['finalized_by'] = 'system';
        if (function_exists('app') && app()->bound('auth')) {
            $authManager = app('auth');
            if (
                method_exists($authManager, 'check') && $authManager->check() &&
                method_exists($authManager, 'user') && $authManager->user()
            ) {
                $report['finalized_by'] = $authManager->user()->email ?? 'system';
            }
        }

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
        $fullPath = Storage::path($path);
        $fileName = basename($path);

        // Email the report
        try {
            Mail::send([], [], function ($message) use ($email, $fullPath, $fileName, $type, $date) {
                $message->to($email)
                    ->subject("ZRA {$type} Report - " . $date->format('Y-m-d'))
                    ->attach($fullPath, [
                        'as' => $fileName,
                        'mime' => 'application/pdf',
                    ]);

                // Add email body as text
                $message->text("Please find attached the ZRA {$type} Report for " . $date->format('Y-m-d') . ".\n\n" .
                    "This is an automated message from the ZRA Smart Invoice System.");
            });

            Log::info("Report successfully emailed to {$email} with attachment {$path}");
        } catch (Exception $e) {
            Log::error("Failed to email report to {$email}", [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            throw $e;
        }
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
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    protected function saveCsv(array $report, string $path): void
    {
        // Create CSV content
        $csvContent = [];

        // Add header row with basic report information
        $csvContent[] = [
            'ZRA ' . $report['type'] . ' Report',
            'Date: ' . $report['date'],
            'Generated: ' . $report['generated_at']
        ];
        $csvContent[] = []; // Empty row for separation

        // Device information section
        $csvContent[] = ['Device Information'];
        $csvContent[] = ['TPIN', 'Branch ID', 'Device Serial', 'Environment'];
        $csvContent[] = [
            $report['device_info']['tpin'],
            $report['device_info']['branch_id'],
            $report['device_info']['device_serial'],
            $report['device_info']['environment']
        ];
        $csvContent[] = []; // Empty row

        // Transaction summary section
        $csvContent[] = ['Transaction Summary'];
        $csvContent[] = ['Type', 'Count', 'Amount', 'Tax'];
        foreach ($report['transaction_summary'] as $type => $data) {
            $csvContent[] = [
                $type,
                $data['count'],
                $data['amount'],
                $data['tax']
            ];
        }
        $csvContent[] = []; // Empty row

        // Tax summary section
        $csvContent[] = ['Tax Summary'];
        $csvContent[] = ['Name', 'Rate', 'Amount'];
        foreach ($report['tax_summary'] as $code => $data) {
            $csvContent[] = [
                $data['name'],
                $data['rate'] . '%',
                $data['amount']
            ];
        }

        // If this is a detailed report with transactions, add them
        if (isset($report['transactions']) && is_array($report['transactions']) && count($report['transactions']) > 0) {
            $csvContent[] = []; // Empty row
            $csvContent[] = ['Detailed Transactions'];
            $csvContent[] = ['ID', 'Reference', 'Type', 'Status', 'Amount', 'Tax', 'Date'];

            foreach ($report['transactions'] as $transaction) {
                $csvContent[] = [
                    $transaction['id'],
                    $transaction['reference'],
                    $transaction['transaction_type'],
                    $transaction['status'],
                    $transaction['total_amount'],
                    $transaction['total_tax'],
                    $transaction['created_at']
                ];
            }
        }

        // Create CSV string
        $csvString = "";
        foreach ($csvContent as $row) {
            $csvString .= implode(',', array_map(function ($cell) {
                // Escape cells that contain commas, quotes, or newlines
                if (preg_match('/[,"\n\r]/', $cell)) {
                    return '"' . str_replace('"', '""', $cell) . '"';
                }
                return $cell;
            }, $row)) . "\n";
        }

        // Save to storage
        Storage::put($path, $csvString);
    }

    /**
     * Save report as PDF
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    protected function savePdf(array $report, string $path): void
    {
        // Use Laravel's dompdf wrapper if available, otherwise use a text-based approach
        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf') || class_exists('\\PDF')) {
            $this->generatePdfWithDomPdf($report, $path);
        } else {
            $this->generatePdfWithFallback($report, $path);
        }
    }

    /**
     * Generate PDF with DomPDF
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    private function generatePdfWithDomPdf(array $report, string $path): void
    {
        try {
            // Convert the report to HTML
            $html = $this->reportToHtml($report);

            // Use Laravel's PDF facade if available
            if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            } elseif (class_exists('\\Barryvdh\\DomPDF\\Pdf')) {
                $pdf = app('\\Barryvdh\\DomPDF\\Pdf');
                $pdf->loadHTML($html);
            } elseif (class_exists('\\Dompdf\\Dompdf')) {
                $pdf = new \Dompdf\Dompdf();
                $pdf->loadHtml($html);
                $pdf->render();
                file_put_contents($path, $pdf->output());
                return;
            } else {
                // No PDF class found, fallback to text-based approach
                throw new Exception('No PDF generation library available');
            }

            // Save PDF to storage
            Storage::put($path, $pdf->output());
        } catch (Exception $e) {
            Log::error('Failed to generate PDF report with DomPDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fall back to text-based approach
            $this->generatePdfWithFallback($report, $path);
        }
    }

    /**
     * Generate PDF with fallback method (text-based PDF)
     *
     * @param array $report
     * @param string $path
     * @return void
     */
    private function generatePdfWithFallback(array $report, string $path): void
    {
        // Get text representation of the report
        $text = $this->formatReportAsText($report, $report['type']);

        // Create a simple PDF file with text content
        // This is a very basic fallback, but it ensures something is generated
        $pdfContent = "%PDF-1.4\n";
        $pdfContent .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdfContent .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdfContent .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
        $pdfContent .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";
        $pdfContent .= "5 0 obj\n<< /Length " . strlen($text) . " >>\nstream\n";
        $pdfContent .= "BT\n/F1 12 Tf\n72 720 Td\n(" . str_replace("\n", ") Tj\n0 -14 Td\n(", $text) . ") Tj\nET\n";
        $pdfContent .= "endstream\nendobj\n";
        $pdfContent .= "xref\n0 6\n0000000000 65535 f\n0000000010 00000 n\n0000000056 00000 n\n0000000111 00000 n\n";
        $pdfContent .= "0000000212 00000 n\n0000000295 00000 n\ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n429\n%%EOF";

        // Save to storage
        Storage::put($path, $pdfContent);
    }

    /**
     * Convert report data to HTML for PDF generation
     *
     * @param array $report
     * @return string
     */
    private function reportToHtml(array $report): string
    {
        $html = "<!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>ZRA {$report['type']} Report - {$report['date']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { font-size: 24px; color: #333; }
                h2 { font-size: 18px; color: #444; margin-top: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; }
                td { padding: 8px; border: 1px solid #ddd; }
                .header { margin-bottom: 30px; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>ZRA " . ucfirst($report['type']) . " Report</h1>
                <p>Date: {$report['date']}</p>
                <p>Generated: {$report['generated_at']}</p>
                <p>Status: {$report['status']}</p>
            </div>
            
            <h2>Device Information</h2>
            <table>
                <tr>
                    <th>TPIN</th>
                    <th>Branch ID</th>
                    <th>Device Serial</th>
                    <th>Environment</th>
                </tr>
                <tr>
                    <td>{$report['device_info']['tpin']}</td>
                    <td>{$report['device_info']['branch_id']}</td>
                    <td>{$report['device_info']['device_serial']}</td>
                    <td>{$report['device_info']['environment']}</td>
                </tr>
            </table>
            
            <h2>Transaction Summary</h2>
            <table>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Amount</th>
                    <th>Tax</th>
                </tr>";

        foreach ($report['transaction_summary'] as $type => $data) {
            $html .= "<tr>
                    <td>" . htmlspecialchars($type) . "</td>
                    <td>{$data['count']}</td>
                    <td>" . number_format($data['amount'], 2) . "</td>
                    <td>" . number_format($data['tax'], 2) . "</td>
                </tr>";
        }

        $html .= "</table>
            
            <h2>Tax Summary</h2>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>";

        foreach ($report['tax_summary'] as $code => $data) {
            $html .= "<tr>
                    <td>" . htmlspecialchars($data['name']) . "</td>
                    <td>{$data['rate']}%</td>
                    <td>" . number_format($data['amount'], 2) . "</td>
                </tr>";
        }

        $html .= "</table>";

        // If this is a detailed report with transactions, add them
        if (isset($report['transactions']) && is_array($report['transactions']) && count($report['transactions']) > 0) {
            $html .= "<h2>Detailed Transactions</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Tax</th>
                    <th>Date</th>
                </tr>";

            foreach ($report['transactions'] as $transaction) {
                $html .= "<tr>
                        <td>{$transaction['id']}</td>
                        <td>{$transaction['reference']}</td>
                        <td>{$transaction['transaction_type']}</td>
                        <td>{$transaction['status']}</td>
                        <td>" . number_format($transaction['total_amount'], 2) . "</td>
                        <td>" . number_format($transaction['total_tax'], 2) . "</td>
                        <td>{$transaction['created_at']}</td>
                    </tr>";
            }

            $html .= "</table>";
        }

        $html .= "<div class='footer'>
                <p>This report was automatically generated by ZRA Smart Invoice System.</p>
            </div>
        </body>
        </html>";

        return $html;
    }
}
