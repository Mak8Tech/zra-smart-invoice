<?php

namespace Mak8Tech\ZraSmartInvoice\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mak8Tech\ZraSmartInvoice\Models\ZraInventory;
use Mak8Tech\ZraSmartInvoice\Models\ZraInventoryMovement;
use Mak8Tech\ZraSmartInvoice\Services\ZraInventoryService;

class ZraInventoryController extends Controller
{
    /**
     * @var ZraInventoryService
     */
    protected $inventoryService;

    /**
     * Constructor
     */
    public function __construct(ZraInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get a list of all inventory products
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        try {
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);
            $category = $request->input('category');
            $active = $request->has('active') ? (bool) $request->input('active') : null;

            $query = ZraInventory::query();

            if ($category) {
                $query->where('category', $category);
            }

            if ($active !== null) {
                $query->where('active', $active);
            }

            $total = $query->count();
            $products = $query->orderBy('name')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return [
                'success' => true,
                'total' => $total,
                'products' => $products,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch inventory products', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch inventory products: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Search for inventory products
     *
     * @param Request $request
     * @return array
     */
    public function search(Request $request): array
    {
        try {
            $query = $request->input('query', '');
            $filters = $request->input('filters', []);
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);

            $products = $this->inventoryService->searchProducts($query, $filters, $limit, $offset);
            $total = ZraInventory::query()
                ->when($query, function ($q) use ($query) {
                    $q->where(function ($subq) use ($query) {
                        $subq->where('sku', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%")
                            ->orWhere('description', 'like', "%{$query}%");
                    });
                })
                ->when(isset($filters['category']) && $filters['category'], function ($q) use ($filters) {
                    $q->where('category', $filters['category']);
                })
                ->when(isset($filters['active']), function ($q) use ($filters) {
                    $q->where('active', $filters['active']);
                })
                ->when(isset($filters['tax_category']) && $filters['tax_category'], function ($q) use ($filters) {
                    $q->where('tax_category', $filters['tax_category']);
                })
                ->when(isset($filters['min_stock']), function ($q) use ($filters) {
                    $q->where('current_stock', '>=', $filters['min_stock']);
                })
                ->when(isset($filters['max_stock']), function ($q) use ($filters) {
                    $q->where('current_stock', '<=', $filters['max_stock']);
                })
                ->count();

            return [
                'success' => true,
                'total' => $total,
                'products' => $products,
            ];
        } catch (Exception $e) {
            Log::error('Failed to search inventory products', [
                'error' => $e->getMessage(),
                'query' => $request->input('query'),
                'filters' => $request->input('filters'),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to search inventory products: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get a specific inventory product
     *
     * @param int $id
     * @return array
     */
    public function show(int $id): array
    {
        try {
            $product = ZraInventory::findOrFail($id);

            return [
                'success' => true,
                'product' => $product,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch inventory product', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch inventory product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new inventory product
     *
     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        try {
            $request->validate([
                'sku' => 'required|string|max:64|unique:zra_inventory,sku',
                'name' => 'required|string|max:255',
                'unit_price' => 'required|numeric|min:0',
                'tax_category' => 'required|string',
            ]);

            $productData = $request->all();
            $product = $this->inventoryService->createProduct($productData);

            return [
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create inventory product', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create inventory product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing inventory product
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, int $id): array
    {
        try {
            $request->validate([
                'sku' => "required|string|max:64|unique:zra_inventory,sku,{$id}",
                'name' => 'required|string|max:255',
                'unit_price' => 'required|numeric|min:0',
                'tax_category' => 'required|string',
            ]);

            $productData = $request->all();
            $product = $this->inventoryService->updateProduct($id, $productData);

            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product,
            ];
        } catch (Exception $e) {
            Log::error('Failed to update inventory product', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $request->all(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update inventory product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an inventory product
     *
     * @param int $id
     * @return array
     */
    public function destroy(int $id): array
    {
        try {
            $product = ZraInventory::findOrFail($id);
            $product->delete();

            return [
                'success' => true,
                'message' => 'Product deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete inventory product', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete inventory product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Adjust stock for a product
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function adjustStock(Request $request, int $id): array
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:0',
                'reason' => 'required|string',
            ]);

            $quantity = $request->input('quantity');
            $reason = $request->input('reason');

            $product = $this->inventoryService->adjustStock($id, $quantity, $reason);

            return [
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'product' => $product,
            ];
        } catch (Exception $e) {
            Log::error('Failed to adjust stock', [
                'error' => $e->getMessage(),
                'id' => $id,
                'quantity' => $request->input('quantity'),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get movement history for a product
     *
     * @param int $id
     * @return array
     */
    public function movements(int $id): array
    {
        try {
            $movements = $this->inventoryService->getProductMovementHistory($id);

            return [
                'success' => true,
                'movements' => $movements,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch movement history', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch movement history: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get low stock products
     *
     * @return array
     */
    public function lowStock(): array
    {
        try {
            $products = $this->inventoryService->getLowStockItems();

            return [
                'success' => true,
                'products' => $products,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch low stock products', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch low stock products: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate inventory report
     *
     * @param Request $request
     * @param string $type
     * @return array
     */
    public function generateReport(Request $request, string $type): array
    {
        try {
            $parameters = $request->all();

            $report = $this->inventoryService->generateInventoryReport($type, $parameters);

            return [
                'success' => true,
                'report' => $report,
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate inventory report', [
                'error' => $e->getMessage(),
                'type' => $type,
                'parameters' => $request->all(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate inventory report: ' . $e->getMessage(),
            ];
        }
    }
}
