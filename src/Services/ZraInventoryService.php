<?php

namespace Mak8Tech\ZraSmartInvoice\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Models\ZraInventory;
use Mak8Tech\ZraSmartInvoice\Models\ZraInventoryMovement;

class ZraInventoryService
{
    /**
     * @var ZraTaxService
     */
    protected $taxService;

    /**
     * Constructor
     *
     * @param ZraTaxService $taxService
     */
    public function __construct(ZraTaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Create a new product in inventory
     *
     * @param array $productData Product data including sku, name, price, etc.
     * @return ZraInventory
     * @throws Exception
     */
    public function createProduct(array $productData): ZraInventory
    {
        // Check if product with SKU already exists
        if (ZraInventory::where('sku', $productData['sku'])->exists()) {
            throw new Exception("Product with SKU {$productData['sku']} already exists");
        }

        // Validate required fields
        $requiredFields = ['sku', 'name', 'unit_price'];
        foreach ($requiredFields as $field) {
            if (!isset($productData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Create the product
        $product = new ZraInventory();
        $product->sku = $productData['sku'];
        $product->name = $productData['name'];
        $product->description = $productData['description'] ?? null;
        $product->category = $productData['category'] ?? null;
        $product->unit_price = $productData['unit_price'];
        $product->tax_category = $productData['tax_category'] ?? config('zra.default_tax_category', 'VAT');
        $product->tax_rate = $productData['tax_rate'] ?? $this->getTaxRateForCategory($product->tax_category);
        $product->unit_of_measure = $productData['unit_of_measure'] ?? 'EACH';
        $product->current_stock = $productData['initial_stock'] ?? 0;
        $product->reorder_level = $productData['reorder_level'] ?? 10;
        $product->track_inventory = $productData['track_inventory'] ?? true;
        $product->active = $productData['active'] ?? true;
        $product->save();

        // If initial stock is provided, record it as an initial adjustment
        if (isset($productData['initial_stock']) && $productData['initial_stock'] > 0) {
            $this->recordMovement(
                $product->id,
                'INITIAL',
                $productData['initial_stock'],
                $productData['unit_price'],
                'Initial stock setup',
                ['reference_type' => 'SYSTEM', 'user' => 'system']
            );
        }

        return $product;
    }

    /**
     * Update an existing product
     *
     * @param int $productId
     * @param array $productData
     * @return ZraInventory
     * @throws Exception
     */
    public function updateProduct(int $productId, array $productData): ZraInventory
    {
        $product = ZraInventory::findOrFail($productId);

        // Check if SKU is being changed and if new SKU already exists
        if (isset($productData['sku']) && $productData['sku'] !== $product->sku) {
            if (ZraInventory::where('sku', $productData['sku'])->exists()) {
                throw new Exception("Product with SKU {$productData['sku']} already exists");
            }
            $product->sku = $productData['sku'];
        }

        // Update basic fields
        if (isset($productData['name'])) $product->name = $productData['name'];
        if (isset($productData['description'])) $product->description = $productData['description'];
        if (isset($productData['category'])) $product->category = $productData['category'];
        if (isset($productData['unit_price'])) $product->unit_price = $productData['unit_price'];

        // Update tax related fields
        if (isset($productData['tax_category'])) {
            $product->tax_category = $productData['tax_category'];
            // Update tax rate based on category unless explicitly provided
            if (!isset($productData['tax_rate'])) {
                $product->tax_rate = $this->getTaxRateForCategory($product->tax_category);
            }
        }
        if (isset($productData['tax_rate'])) $product->tax_rate = $productData['tax_rate'];

        // Update other fields
        if (isset($productData['unit_of_measure'])) $product->unit_of_measure = $productData['unit_of_measure'];
        if (isset($productData['reorder_level'])) $product->reorder_level = $productData['reorder_level'];
        if (isset($productData['track_inventory'])) $product->track_inventory = $productData['track_inventory'];
        if (isset($productData['active'])) $product->active = $productData['active'];

        $product->save();

        return $product;
    }

    /**
     * Update product stock quantity
     *
     * @param int $productId
     * @param int $quantity New quantity
     * @param string $reason Reason for adjustment
     * @return ZraInventory
     */
    public function adjustStock(int $productId, int $quantity, string $reason = 'Stock adjustment'): ZraInventory
    {
        $product = ZraInventory::findOrFail($productId);

        // Calculate the adjustment quantity
        $adjustmentQty = $quantity - $product->current_stock;

        // Record the movement
        if ($adjustmentQty !== 0) {
            $this->recordMovement(
                $productId,
                'ADJUSTMENT',
                $adjustmentQty,
                $product->unit_price,
                $reason,
                ['reference_type' => 'ADJUSTMENT', 'reason' => $reason]
            );

            // Update current stock
            $product->current_stock = $quantity;
            $product->save();
        }

        return $product;
    }

    /**
     * Record inventory movement (e.g., sales, purchases, adjustments)
     *
     * @param int $productId
     * @param string $movementType
     * @param int $quantity
     * @param float $unitPrice
     * @param string $reference
     * @param array $metadata
     * @return ZraInventoryMovement
     */
    public function recordMovement(
        int $productId,
        string $movementType,
        int $quantity,
        float $unitPrice,
        string $reference,
        array $metadata = []
    ): ZraInventoryMovement {

        $movement = new ZraInventoryMovement();
        $movement->inventory_id = $productId;
        $movement->movement_type = $movementType;
        $movement->quantity = $quantity;
        $movement->unit_price = $unitPrice;
        $movement->reference = $reference;
        $movement->metadata = $metadata;
        $movement->save();

        // Update product stock level if not an initial movement
        if ($movementType !== 'INITIAL') {
            $product = ZraInventory::findOrFail($productId);
            $product->current_stock += $quantity;
            $product->save();
        }

        return $movement;
    }

    /**
     * Process inventory for a sales transaction
     *
     * @param array $items Array of items in the sale with product_id, quantity, etc.
     * @param string $reference Sales reference number
     * @return array Items with updated availability status
     * @throws Exception If items are not available in sufficient quantities
     */
    public function processSaleItems(array $items, string $reference): array
    {
        $result = [];
        $unavailableItems = [];

        // First check if all items are available in sufficient quantities
        foreach ($items as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                throw new Exception("Each sale item must have product_id and quantity");
            }

            $product = ZraInventory::find($item['product_id']);

            // Skip availability check if product doesn't exist or inventory tracking is disabled
            if (!$product || !$product->track_inventory) {
                $result[] = array_merge($item, [
                    'available' => true,
                    'product' => $product ? $product->toArray() : null
                ]);
                continue;
            }

            // Check if sufficient stock is available
            if ($product->current_stock < $item['quantity']) {
                $unavailableItems[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $product->current_stock
                ];

                $result[] = array_merge($item, [
                    'available' => false,
                    'product' => $product->toArray()
                ]);
            } else {
                $result[] = array_merge($item, [
                    'available' => true,
                    'product' => $product->toArray()
                ]);
            }
        }

        // If any items are unavailable, stop processing
        if (!empty($unavailableItems)) {
            throw new Exception(
                "Some items are not available in sufficient quantities: " .
                    json_encode($unavailableItems)
            );
        }

        // Process inventory movements for each item
        DB::beginTransaction();
        try {
            foreach ($result as &$item) {
                $product = ZraInventory::find($item['product_id']);

                // Skip if product doesn't exist or inventory tracking is disabled
                if (!$product || !$product->track_inventory) {
                    continue;
                }

                // Record inventory movement (negative quantity for sales)
                $this->recordMovement(
                    $product->id,
                    'SALE',
                    -1 * $item['quantity'],
                    $item['unit_price'] ?? $product->unit_price,
                    $reference,
                    [
                        'reference_type' => 'SALE',
                        'sale_reference' => $reference,
                        'item_data' => $item
                    ]
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to process sale inventory movements', [
                'error' => $e->getMessage(),
                'items' => $items,
                'reference' => $reference
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * Process inventory for a purchase transaction (restocking)
     *
     * @param array $items Array of items purchased with product_id, quantity, etc.
     * @param string $reference Purchase reference number
     * @return array Items with updated stock information
     */
    public function processPurchaseItems(array $items, string $reference): array
    {
        $result = [];

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    throw new Exception("Each purchase item must have product_id and quantity");
                }

                $product = ZraInventory::find($item['product_id']);

                // Skip if product doesn't exist or inventory tracking is disabled
                if (!$product || !$product->track_inventory) {
                    $result[] = array_merge($item, [
                        'processed' => false,
                        'reason' => $product ? 'Inventory tracking disabled' : 'Product not found',
                        'product' => $product ? $product->toArray() : null
                    ]);
                    continue;
                }

                // Record inventory movement (positive quantity for purchases)
                $this->recordMovement(
                    $product->id,
                    'PURCHASE',
                    $item['quantity'],
                    $item['unit_price'] ?? $product->unit_price,
                    $reference,
                    [
                        'reference_type' => 'PURCHASE',
                        'purchase_reference' => $reference,
                        'item_data' => $item
                    ]
                );

                $result[] = array_merge($item, [
                    'processed' => true,
                    'previous_stock' => $product->current_stock,
                    'new_stock' => $product->current_stock + $item['quantity'],
                    'product' => $product->toArray()
                ]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to process purchase inventory movements', [
                'error' => $e->getMessage(),
                'items' => $items,
                'reference' => $reference
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * Get low stock items
     *
     * @param int $limit
     * @return Collection
     */
    public function getLowStockItems(int $limit = 50): Collection
    {
        return ZraInventory::where('track_inventory', true)
            ->where('active', true)
            ->whereRaw('current_stock <= reorder_level')
            ->orderByRaw('(current_stock / reorder_level)')
            ->limit($limit)
            ->get();
    }

    /**
     * Get inventory movement history for a product
     *
     * @param int $productId
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function getProductMovementHistory(int $productId, int $limit = 100, int $offset = 0): Collection
    {
        return ZraInventoryMovement::where('inventory_id', $productId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Check if product is available in the requested quantity
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function isProductAvailable(int $productId, int $quantity): bool
    {
        $product = ZraInventory::find($productId);

        // If product doesn't exist or inventory tracking is disabled, assume available
        if (!$product || !$product->track_inventory) {
            return true;
        }

        return $product->current_stock >= $quantity;
    }

    /**
     * Get tax rate for a tax category
     *
     * @param string $taxCategory
     * @return float
     */
    protected function getTaxRateForCategory(string $taxCategory): float
    {
        $taxCategories = config('zra.tax_categories', []);

        if (isset($taxCategories[$taxCategory])) {
            return $taxCategories[$taxCategory]['default_rate'] ?? 0;
        }

        // Default to standard VAT rate
        return $taxCategories['VAT']['default_rate'] ?? 16.0;
    }

    /**
     * Search inventory products
     *
     * @param string $query Search term
     * @param array $filters Filters (category, active status, etc.)
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function searchProducts(string $query, array $filters = [], int $limit = 50, int $offset = 0): Collection
    {
        $products = ZraInventory::query();

        // Apply search term
        if ($query) {
            $products->where(function ($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['category']) && $filters['category']) {
            $products->where('category', $filters['category']);
        }

        if (isset($filters['active'])) {
            $products->where('active', $filters['active']);
        }

        if (isset($filters['tax_category']) && $filters['tax_category']) {
            $products->where('tax_category', $filters['tax_category']);
        }

        if (isset($filters['min_stock'])) {
            $products->where('current_stock', '>=', $filters['min_stock']);
        }

        if (isset($filters['max_stock'])) {
            $products->where('current_stock', '<=', $filters['max_stock']);
        }

        // Apply pagination
        return $products->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Generate inventory report
     *
     * @param string $type Report type (summary, detail, value, movement)
     * @param array $parameters Additional parameters
     * @return array
     */
    public function generateInventoryReport(string $type, array $parameters = []): array
    {
        switch ($type) {
            case 'summary':
                return $this->generateSummaryReport($parameters);
            case 'detail':
                return $this->generateDetailReport($parameters);
            case 'value':
                return $this->generateValueReport($parameters);
            case 'movement':
                return $this->generateMovementReport($parameters);
            default:
                throw new Exception("Invalid report type: {$type}");
        }
    }

    /**
     * Generate summary inventory report
     *
     * @param array $parameters
     * @return array
     */
    protected function generateSummaryReport(array $parameters): array
    {
        $activeCount = ZraInventory::where('active', true)->count();
        $totalCount = ZraInventory::count();
        $lowStockCount = ZraInventory::where('track_inventory', true)
            ->where('active', true)
            ->whereRaw('current_stock <= reorder_level')
            ->count();

        $totalValue = ZraInventory::where('active', true)
            ->selectRaw('SUM(current_stock * unit_price) as total_value')
            ->first()
            ->total_value ?? 0;

        $categorySummary = ZraInventory::where('active', true)
            ->selectRaw('category, COUNT(*) as count, SUM(current_stock) as total_stock, SUM(current_stock * unit_price) as total_value')
            ->groupBy('category')
            ->get();

        return [
            'type' => 'summary',
            'generated_at' => Carbon::now()->toDateTimeString(),
            'active_products' => $activeCount,
            'total_products' => $totalCount,
            'low_stock_products' => $lowStockCount,
            'total_inventory_value' => $totalValue,
            'category_summary' => $categorySummary
        ];
    }

    /**
     * Generate detailed inventory report
     *
     * @param array $parameters
     * @return array
     */
    protected function generateDetailReport(array $parameters): array
    {
        $query = ZraInventory::query();

        // Apply filters
        if (isset($parameters['active'])) {
            $query->where('active', $parameters['active']);
        }

        if (isset($parameters['category'])) {
            $query->where('category', $parameters['category']);
        }

        if (isset($parameters['low_stock_only']) && $parameters['low_stock_only']) {
            $query->where('track_inventory', true)
                ->whereRaw('current_stock <= reorder_level');
        }

        $products = $query->orderBy('name')->get();

        return [
            'type' => 'detail',
            'generated_at' => Carbon::now()->toDateTimeString(),
            'filters' => $parameters,
            'product_count' => $products->count(),
            'products' => $products
        ];
    }

    /**
     * Generate inventory value report
     *
     * @param array $parameters
     * @return array
     */
    protected function generateValueReport(array $parameters): array
    {
        $query = ZraInventory::where('active', true);

        // Apply filters
        if (isset($parameters['category'])) {
            $query->where('category', $parameters['category']);
        }

        $products = $query->orderByRaw('current_stock * unit_price DESC')->get();

        $totalValue = $products->sum(function ($product) {
            return $product->current_stock * $product->unit_price;
        });

        return [
            'type' => 'value',
            'generated_at' => Carbon::now()->toDateTimeString(),
            'filters' => $parameters,
            'total_value' => $totalValue,
            'product_count' => $products->count(),
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category,
                    'current_stock' => $product->current_stock,
                    'unit_price' => $product->unit_price,
                    'value' => $product->current_stock * $product->unit_price
                ];
            })
        ];
    }

    /**
     * Generate inventory movement report
     *
     * @param array $parameters
     * @return array
     */
    protected function generateMovementReport(array $parameters): array
    {
        $query = ZraInventoryMovement::query();

        // Apply filters
        if (isset($parameters['product_id'])) {
            $query->where('inventory_id', $parameters['product_id']);
        }

        if (isset($parameters['movement_type'])) {
            $query->where('movement_type', $parameters['movement_type']);
        }

        if (isset($parameters['start_date'])) {
            $query->where('created_at', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('created_at', '<=', $parameters['end_date']);
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        return [
            'type' => 'movement',
            'generated_at' => Carbon::now()->toDateTimeString(),
            'filters' => $parameters,
            'movement_count' => $movements->count(),
            'movements' => $movements
        ];
    }
}
