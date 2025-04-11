<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZraInventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'zra_inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'category',
        'unit_price',
        'tax_category',
        'tax_rate',
        'unit_of_measure',
        'current_stock',
        'reorder_level',
        'track_inventory',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'unit_price' => 'float',
        'tax_rate' => 'float',
        'current_stock' => 'integer',
        'reorder_level' => 'integer',
        'track_inventory' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the movements for the inventory item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(ZraInventoryMovement::class, 'inventory_id');
    }

    /**
     * Get active products
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get products with low stock
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereRaw('current_stock <= reorder_level');
    }

    /**
     * Check if this product has sufficient stock for the requested quantity
     *
     * @param int $quantity
     * @return bool
     */
    public function hasStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->current_stock >= $quantity;
    }

    /**
     * Calculate the value of current stock
     *
     * @return float
     */
    public function getStockValueAttribute(): float
    {
        return $this->current_stock * $this->unit_price;
    }

    /**
     * Calculate the tax for this product based on quantity
     *
     * @param int $quantity
     * @return array
     */
    public function calculateTax(int $quantity = 1): array
    {
        $totalBeforeTax = $this->unit_price * $quantity;
        $taxAmount = 0;

        // Calculate tax if rate is greater than zero
        if ($this->tax_rate > 0) {
            $taxAmount = $totalBeforeTax * ($this->tax_rate / 100);
            // Round tax amount according to standard conventions
            $taxAmount = round($taxAmount, 2);
        }

        $totalAmount = $totalBeforeTax + $taxAmount;

        return [
            'total_before_tax' => $totalBeforeTax,
            'tax_amount' => $taxAmount,
            'tax_rate' => $this->tax_rate,
            'tax_category' => $this->tax_category,
            'total_amount' => $totalAmount,
        ];
    }
}
