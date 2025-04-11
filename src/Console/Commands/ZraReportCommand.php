<?php

namespace Mak8Tech\ZraSmartInvoice\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Services\ZraReportService;

class ZraReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zra:report
                            {type=daily : The type of report to generate (x|z|daily|monthly)}
                            {--date= : Date for report in Y-m-d format}
                            {--output=console : Output format (console|json|csv|pdf)}
                            {--save : Save the report to storage}
                            {--email= : Email address to send report to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ZRA reports (X and Z reports) for auditing purposes';

    /**
     * @var ZraReportService
     */
    protected $reportService;

    /**
     * Create a new command instance.
     *
     * @param ZraReportService $reportService
     * @return void
     */
    public function __construct(ZraReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $type = $this->argument('type');
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
            $output = $this->option('output');
            $save = $this->option('save');
            $email = $this->option('email');

            $this->info("Generating {$type} report for {$date->format('Y-m-d')}...");

            // Validate report type
            if (!in_array($type, ['x', 'z', 'daily', 'monthly'])) {
                $this->error("Invalid report type. Must be one of: x, z, daily, monthly");
                return 1;
            }

            // Generate the report
            $report = $this->reportService->generateReport($type, $date);

            // Handle the output
            switch ($output) {
                case 'json':
                    $this->output->writeln(json_encode($report, JSON_PRETTY_PRINT));
                    break;
                case 'csv':
                    $this->outputCsv($report);
                    break;
                case 'pdf':
                    $this->info("PDF output is not supported via console. Please use --save option.");
                    break;
                case 'console':
                default:
                    $this->displayReport($report, $type);
                    break;
            }

            // Save the report if requested
            if ($save) {
                $path = $this->reportService->saveReport($report, $type, $date, $output);
                $this->info("Report saved to: {$path}");
            }

            // Email the report if requested
            if ($email) {
                $this->reportService->emailReport($report, $type, $date, $email);
                $this->info("Report sent to {$email}");
            }

            $this->info("Report generation completed successfully.");
            return 0;
        } catch (Exception $e) {
            $this->error("Error generating report: {$e->getMessage()}");
            Log::error("ZRA Report Generation Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Display the report in a formatted table
     *
     * @param array $report
     * @param string $type
     * @return void
     */
    protected function displayReport(array $report, string $type): void
    {
        $this->info("=== ZRA {$type} Report ===");
        $this->info("Date: {$report['date']}");
        $this->info("Device: {$report['device_info']['device_serial']}");
        $this->info("Status: {$report['status']}");

        $this->info("\nTransaction Summary:");
        $this->table(
            ['Type', 'Count', 'Total Amount', 'Total Tax'],
            $this->formatTransactionSummary($report['transaction_summary'])
        );

        $this->info("\nTax Summary:");
        $this->table(
            ['Category', 'Rate', 'Amount'],
            $this->formatTaxSummary($report['tax_summary'])
        );

        // Only show transaction list for X reports (interim reports)
        if ($type === 'x' && isset($report['transactions'])) {
            $this->info("\nTransactions:");
            $this->table(
                ['Time', 'Reference', 'Type', 'Amount', 'Tax', 'Status'],
                $this->formatTransactions($report['transactions'])
            );
        }

        // Show daily totals for monthly reports
        if ($type === 'monthly' && isset($report['daily_totals'])) {
            $this->info("\nDaily Totals:");
            $this->table(
                ['Date', 'Transactions', 'Amount', 'Tax'],
                $this->formatDailyTotals($report['daily_totals'])
            );
        }
    }

    /**
     * Format transaction summary for display
     *
     * @param array $summary
     * @return array
     */
    protected function formatTransactionSummary(array $summary): array
    {
        $rows = [];
        foreach ($summary as $type => $data) {
            $rows[] = [
                $type,
                $data['count'],
                number_format($data['amount'], 2),
                number_format($data['tax'], 2),
            ];
        }
        return $rows;
    }

    /**
     * Format tax summary for display
     *
     * @param array $summary
     * @return array
     */
    protected function formatTaxSummary(array $summary): array
    {
        $rows = [];
        foreach ($summary as $category => $data) {
            $rows[] = [
                $data['name'] ?? $category,
                $data['rate'] . '%',
                number_format($data['amount'], 2),
            ];
        }
        return $rows;
    }

    /**
     * Format transactions for display
     *
     * @param array $transactions
     * @return array
     */
    protected function formatTransactions(array $transactions): array
    {
        $rows = [];
        foreach ($transactions as $transaction) {
            $rows[] = [
                $transaction['created_at'],
                $transaction['reference'],
                $transaction['transaction_type'],
                number_format($transaction['total_amount'] ?? 0, 2),
                number_format($transaction['total_tax'] ?? 0, 2),
                $transaction['status'],
            ];
        }
        return $rows;
    }

    /**
     * Format daily totals for display
     *
     * @param array $dailyTotals
     * @return array
     */
    protected function formatDailyTotals(array $dailyTotals): array
    {
        $rows = [];
        foreach ($dailyTotals as $date => $data) {
            $rows[] = [
                $date,
                $data['count'],
                number_format($data['amount'], 2),
                number_format($data['tax'], 2),
            ];
        }
        return $rows;
    }

    /**
     * Output the report as CSV
     *
     * @param array $report
     * @return void
     */
    protected function outputCsv(array $report): void
    {
        // Implementation for CSV output
        $this->info("CSV output format will be implemented in a future update.");
    }
}
