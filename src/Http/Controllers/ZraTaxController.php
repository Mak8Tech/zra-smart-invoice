<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Services\ZraTaxService;

class ZraTaxController extends Controller
{
    /**
     * @var ZraTaxService
     */
    protected $taxService;

    /**
     * Constructor
     */
    public function __construct(ZraTaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Get available tax categories
     *
     * @return array
     */
    public function categories(): array
    {
        try {
            return $this->taxService->getTaxCategories();
        } catch (Exception $e) {
            Log::error('Failed to get tax categories', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get tax categories: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get exemption categories
     *
     * @return array
     */
    public function exemptions(): array
    {
        try {
            return $this->taxService->getExemptionCategories();
        } catch (Exception $e) {
            Log::error('Failed to get exemption categories', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get exemption categories: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate tax for items
     *
     * @param Request $request
     * @return array
     */
    public function calculate(Request $request): array
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.unitPrice' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|numeric|min:0',
            ]);

            $items = $request->input('items');
            $result = $this->taxService->calculateInvoiceTax($items);

            return [
                'success' => true,
                'calculation' => $result,
            ];
        } catch (Exception $e) {
            Log::error('Failed to calculate tax', [
                'error' => $e->getMessage(),
                'items' => $request->input('items'),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to calculate tax: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format tax for API submission
     *
     * @param Request $request
     * @return array
     */
    public function formatForApi(Request $request): array
    {
        try {
            $request->validate([
                'calculatedTax' => 'required|array',
            ]);

            $calculatedTax = $request->input('calculatedTax');
            $result = $this->taxService->formatTaxForApi($calculatedTax);

            return [
                'success' => true,
                'formatted' => $result,
            ];
        } catch (Exception $e) {
            Log::error('Failed to format tax for API', [
                'error' => $e->getMessage(),
                'data' => $request->input('calculatedTax'),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to format tax for API: ' . $e->getMessage(),
            ];
        }
    }
}
