<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ZraTaxService
{
    /**
     * Calculate tax for a single item based on its price, quantity, and tax category
     *
     * @param float $unitPrice The unit price of the item
     * @param float $quantity The quantity of the item
     * @param string $taxCategory The tax category code (e.g., VAT, TOURISM_LEVY, EXCISE, ZERO_RATED, EXEMPT)
     * @param string|null $exemptionCategory Optional exemption category
     * @return array Returns an array with totalAmount, taxAmount, taxRate, and taxCategory
     * @throws Exception If tax category is invalid
     */
    public function calculateItemTax(
        float $unitPrice,
        float $quantity,
        string $taxCategory = null,
        string $exemptionCategory = null
    ): array {
        // Default to the configured default tax category if none provided
        $taxCategory = $taxCategory ?? config('zra.default_tax_category');

        // Validate tax category
        $taxCategories = config('zra.tax_categories');
        if (!array_key_exists($taxCategory, $taxCategories)) {
            throw new Exception("Invalid tax category: {$taxCategory}");
        }

        // Get tax rate for the category
        $taxRate = $taxCategories[$taxCategory]['default_rate'];

        // Check for exemption
        if ($exemptionCategory) {
            $exemptionCategories = config('zra.exemption_categories');
            if (!array_key_exists($exemptionCategory, $exemptionCategories)) {
                throw new Exception("Invalid exemption category: {$exemptionCategory}");
            }

            // If exempt, set tax rate to zero
            $taxRate = 0;
        }

        // Calculate total amount before tax
        $totalBeforeTax = $unitPrice * $quantity;

        // Calculate tax amount
        $taxAmount = 0;
        if ($taxRate > 0) {
            $taxAmount = $totalBeforeTax * ($taxRate / 100);
        }

        // Round tax amount according to configuration
        $taxRounding = config('zra.tax_rounding', 2);
        $taxAmount = round($taxAmount, $taxRounding);

        // Calculate total amount including tax
        $totalAmount = $totalBeforeTax + $taxAmount;

        return [
            'total_before_tax' => $totalBeforeTax,
            'total_amount' => $totalAmount,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'tax_category' => $taxCategory,
            'exemption_category' => $exemptionCategory,
        ];
    }

    /**
     * Calculate tax for multiple items
     *
     * @param array $items An array of items, each with unitPrice, quantity, and optionally taxCategory and exemptionCategory
     * @return array Returns an array with totalBeforeTax, totalAmount, totalTax, and items with tax details
     */
    public function calculateInvoiceTax(array $items): array
    {
        $calculatedItems = [];
        $totalBeforeTax = 0;
        $totalTaxAmount = 0;
        $taxSummary = [];

        foreach ($items as $item) {
            // Ensure required fields exist
            if (!isset($item['unitPrice']) || !isset($item['quantity'])) {
                throw new Exception("Each item must have unitPrice and quantity");
            }

            // Get tax category and exemption if provided
            $taxCategory = $item['taxCategory'] ?? null;
            $exemptionCategory = $item['exemptionCategory'] ?? null;

            // Calculate tax for this item
            $taxCalculation = $this->calculateItemTax(
                $item['unitPrice'],
                $item['quantity'],
                $taxCategory,
                $exemptionCategory
            );

            // Update running totals
            $totalBeforeTax += $taxCalculation['total_before_tax'];
            $totalTaxAmount += $taxCalculation['tax_amount'];

            // Track tax by category for summary
            $category = $taxCalculation['tax_category'];
            if (!isset($taxSummary[$category])) {
                $taxSummary[$category] = [
                    'tax_amount' => 0,
                    'tax_rate' => $taxCalculation['tax_rate'],
                    'name' => config('zra.tax_categories.' . $category . '.name'),
                ];
            }
            $taxSummary[$category]['tax_amount'] += $taxCalculation['tax_amount'];

            // Add calculated tax info to the item
            $calculatedItems[] = array_merge($item, [
                'totalBeforeTax' => $taxCalculation['total_before_tax'],
                'totalAmount' => $taxCalculation['total_amount'],
                'taxAmount' => $taxCalculation['tax_amount'],
                'taxRate' => $taxCalculation['tax_rate'],
                'taxCategory' => $taxCalculation['tax_category'],
                'exemptionCategory' => $taxCalculation['exemption_category'],
            ]);
        }

        // Calculate grand total
        $totalAmount = $totalBeforeTax + $totalTaxAmount;

        return [
            'items' => $calculatedItems,
            'total_before_tax' => $totalBeforeTax,
            'total_tax' => $totalTaxAmount,
            'total_amount' => $totalAmount,
            'tax_summary' => $taxSummary,
        ];
    }

    /**
     * Format tax data for ZRA API submission
     *
     * @param array $calculatedTax The result from calculateInvoiceTax
     * @return array Formatted tax data for API submission
     */
    public function formatTaxForApi(array $calculatedTax): array
    {
        $formattedItems = [];

        foreach ($calculatedTax['items'] as $item) {
            $formattedItems[] = [
                'name' => $item['name'] ?? 'Product',
                'quantity' => $item['quantity'],
                'unitPrice' => $item['unitPrice'],
                'totalAmount' => $item['totalAmount'],
                'taxRate' => $item['taxRate'],
                'taxAmount' => $item['taxAmount'],
                'taxCategory' => $item['taxCategory'],
                'exemptionCategory' => $item['exemptionCategory'] ?? null,
            ];
        }

        return [
            'items' => $formattedItems,
            'totalAmount' => $calculatedTax['total_amount'],
            'totalTax' => $calculatedTax['total_tax'],
            'taxSummary' => $calculatedTax['tax_summary'],
        ];
    }

    /**
     * Check if a tax category is zero-rated
     *
     * @param string $taxCategory
     * @return bool
     */
    public function isZeroRated(string $taxCategory): bool
    {
        return $taxCategory === 'ZERO_RATED';
    }

    /**
     * Check if a tax category is exempt
     *
     * @param string $taxCategory
     * @return bool
     */
    public function isExempt(string $taxCategory): bool
    {
        return $taxCategory === 'EXEMPT';
    }

    /**
     * Get a list of all available tax categories
     *
     * @return array
     */
    public function getTaxCategories(): array
    {
        $categories = [];
        foreach (config('zra.tax_categories') as $code => $category) {
            $categories[] = [
                'code' => $code,
                'name' => $category['name'],
                'rate' => $category['default_rate'],
            ];
        }
        return $categories;
    }

    /**
     * Get a list of all exemption categories
     *
     * @return array
     */
    public function getExemptionCategories(): array
    {
        $categories = [];
        foreach (config('zra.exemption_categories') as $code => $name) {
            $categories[] = [
                'code' => $code,
                'name' => $name,
            ];
        }
        return $categories;
    }
}
