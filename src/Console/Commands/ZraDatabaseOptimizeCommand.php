<?php

namespace Mak8Tech\ZraSmartInvoice\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ZraDatabaseOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zra:optimize-db 
                            {--analyze : Run ANALYZE TABLE on ZRA tables} 
                            {--optimize : Run OPTIMIZE TABLE on ZRA tables}
                            {--truncate-logs : Truncate old transaction logs}
                            {--days=90 : Number of days of logs to keep when truncating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize ZRA Smart Invoice database tables';

    /**
     * ZRA tables to optimize
     *
     * @var array
     */
    protected $tables = [
        'zra_configs',
        'zra_transaction_logs',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('ZRA Smart Invoice Database Optimization');
        $this->line('----------------------------------------');

        // Check if any options were specified
        if (!$this->option('analyze') && !$this->option('optimize') && !$this->option('truncate-logs')) {
            if ($this->confirm('No options specified. Would you like to run all optimizations?', true)) {
                $this->input->setOption('analyze', true);
                $this->input->setOption('optimize', true);
                $this->input->setOption('truncate-logs', true);
            } else {
                $this->info('No optimization tasks performed.');
                return 0;
            }
        }

        // Check tables exist before proceeding
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Table '{$table}' does not exist.");
                return 1;
            }
        }

        // Run analyze tables
        if ($this->option('analyze')) {
            $this->analyzeZraTables();
        }

        // Run optimize tables
        if ($this->option('optimize')) {
            $this->optimizeZraTables();
        }

        // Truncate old logs
        if ($this->option('truncate-logs')) {
            $this->truncateOldLogs();
        }

        $this->info('Database optimization complete!');
        return 0;
    }

    /**
     * Analyze ZRA tables
     *
     * @return void
     */
    protected function analyzeZraTables()
    {
        $this->info('Analyzing tables...');
        
        $bar = $this->output->createProgressBar(count($this->tables));
        $bar->start();
        
        foreach ($this->tables as $table) {
            try {
                // Run ANALYZE TABLE statement
                DB::statement("ANALYZE TABLE {$table}");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error analyzing table '{$table}': " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Optimize ZRA tables
     *
     * @return void
     */
    protected function optimizeZraTables()
    {
        $this->info('Optimizing tables...');
        
        $bar = $this->output->createProgressBar(count($this->tables));
        $bar->start();
        
        foreach ($this->tables as $table) {
            try {
                // Run OPTIMIZE TABLE statement
                DB::statement("OPTIMIZE TABLE {$table}");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error optimizing table '{$table}': " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Truncate old transaction logs
     *
     * @return void
     */
    protected function truncateOldLogs()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days)->format('Y-m-d H:i:s');
        
        $this->info("Removing transaction logs older than {$days} days ({$cutoffDate})...");
        
        try {
            $count = DB::table('zra_transaction_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
                
            $this->info("Successfully removed {$count} old transaction logs.");
        } catch (\Exception $e) {
            $this->error("Error truncating old logs: " . $e->getMessage());
        }
    }
}
