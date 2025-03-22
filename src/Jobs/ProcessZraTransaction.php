<?php

namespace Mak8Tech\ZraSmartInvoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mak8Tech\ZraSmartInvoice\Services\ZraService;
use Throwable;

class ProcessZraTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The transaction type.
     *
     * @var string
     */
    protected $transactionType;

    /**
     * The transaction data.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $transactionType, array $data)
    {
        $this->transactionType = $transactionType;
        $this->data = $data;
        $this->tries = config('zra.retry.attempts', 3);
    }

    /**
     * Execute the job.
     */
    public function handle(ZraService $zraService): void
    {
        try {
            switch ($this->transactionType) {
                case 'sales':
                    $zraService->sendSalesData($this->data);
                    break;
                case 'purchase':
                    $zraService->sendPurchaseData($this->data);
                    break;
                case 'stock':
                    $zraService->sendStockData($this->data);
                    break;
                default:
                    throw new \Exception('Unsupported transaction type: ' . $this->transactionType);
            }
        } catch (Throwable $e) {
            // Log the error
            \Illuminate\Support\Facades\Log::error("ZRA {$this->transactionType} job failed: {$e->getMessage()}", [
                'exception' => $e,
                'data' => array_map(function ($value) {
                    return is_string($value) && strlen($value) > 100 
                        ? substr($value, 0, 100) . '...' 
                        : $value;
                }, $this->data)
            ]);
            
            throw $e;
        }
    }
}
