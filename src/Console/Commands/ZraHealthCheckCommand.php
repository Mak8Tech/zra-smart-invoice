<?php

namespace Mak8Tech\ZraSmartInvoice\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mak8Tech\ZraSmartInvoice\Models\ZraConfig;
use Mak8Tech\ZraSmartInvoice\Models\ZraTransactionLog;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;

class ZraHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zra:health-check 
                            {--ping : Test the connection to ZRA API}
                            {--cleanup=0 : Delete transaction logs older than specified days (0 means no cleanup)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run health checks on the ZRA integration';

    /**
     * Execute the console command.
     */
    public function handle(ZraService $zraService)
    {
        $this->info('ZRA Smart Invoice Health Check');
        $this->info('==============================');

        // Check DB connection
        $this->checkDatabaseConnection();
        
        // Check configuration
        $this->checkConfiguration();
        
        // Check API connection if requested
        if ($this->option('ping')) {
            $this->checkApiConnection($zraService);
        }
        
        // Show transaction statistics
        $this->showTransactionStats();
        
        // Cleanup old logs if requested
        $daysToKeep = (int)$this->option('cleanup');
        if ($daysToKeep > 0) {
            $this->cleanupOldLogs($daysToKeep);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Check database connection.
     */
    private function checkDatabaseConnection(): void
    {
        $this->info('Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ Database connection: FAILED');
            $this->error('  Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Check ZRA configuration.
     */
    private function checkConfiguration(): void
    {
        $this->info('Checking ZRA configuration...');
        
        $config = ZraConfig::getActive();
        if (!$config) {
            $this->warn('! Configuration: NOT FOUND');
            return;
        }
        
        $this->info('✓ Configuration: FOUND');
        $this->info('  • TPIN: ' . substr($config->tpin, 0, 3) . '****' . substr($config->tpin, -3));
        $this->info('  • Branch ID: ' . $config->branch_id);
        $this->info('  • Environment: ' . $config->environment);
        $this->info('  • Device Status: ' . ($config->isInitialized() ? 'Initialized' : 'Not Initialized'));
        
        if ($config->isInitialized()) {
            $this->info('  • Last Initialized: ' . $config->last_initialized_at->diffForHumans());
        }
        
        if ($config->last_sync_at) {
            $this->info('  • Last Sync: ' . $config->last_sync_at->diffForHumans());
        }
    }
    
    /**
     * Check API connection.
     */
    private function checkApiConnection(ZraService $zraService): void
    {
        $this->info('Testing API connection...');
        
        if (!$zraService->isInitialized()) {
            $this->warn('! Cannot test API connection: Device not initialized');
            return;
        }
        
        try {
            // Create a test ping endpoint method in ZraService if available
            // For now, we'll use a basic request to check connectivity
            $baseUrl = config('zra.base_url');
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $response = $client->request('GET', $baseUrl, ['http_errors' => false]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode >= 200 && $statusCode < 500) {
                $this->info('✓ API connection: OK (Status ' . $statusCode . ')');
            } else {
                $this->error('✗ API connection: FAILED (Status ' . $statusCode . ')');
            }
        } catch (\Exception $e) {
            $this->error('✗ API connection: FAILED');
            $this->error('  Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Show transaction statistics.
     */
    private function showTransactionStats(): void
    {
        $this->info('Transaction statistics:');
        
        $totalCount = ZraTransactionLog::count();
        $successCount = ZraTransactionLog::where('status', 'success')->count();
        $failedCount = ZraTransactionLog::where('status', 'failed')->count();
        
        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;
        
        $this->info('  • Total transactions: ' . $totalCount);
        $this->info('  • Successful: ' . $successCount);
        $this->info('  • Failed: ' . $failedCount);
        $this->info('  • Success rate: ' . $successRate . '%');
        
        $latestLog = ZraTransactionLog::latest()->first();
        if ($latestLog) {
            $this->info('  • Last transaction: ' . $latestLog->created_at->diffForHumans() . ' (' . $latestLog->status . ')');
        }
    }
    
    /**
     * Clean up old logs.
     */
    private function cleanupOldLogs(int $daysToKeep): void
    {
        $this->info('Cleaning up logs older than ' . $daysToKeep . ' days...');
        
        $cutoffDate = now()->subDays($daysToKeep);
        $count = ZraTransactionLog::where('created_at', '<', $cutoffDate)->count();
        
        if ($count === 0) {
            $this->info('  No logs to clean up.');
            return;
        }
        
        if ($this->confirm('Found ' . $count . ' logs to delete. Continue?', true)) {
            ZraTransactionLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info('  ✓ Deleted ' . $count . ' old transaction logs.');
        } else {
            $this->info('  Cleanup cancelled.');
        }
    }
}
