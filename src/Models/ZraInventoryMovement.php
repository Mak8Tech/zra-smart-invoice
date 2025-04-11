<?php

namespace Mak8Tech\ZraSmartInvoice\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZraInventoryMovement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'zra_inventory_movements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventory_id',
        'reference',
        'movement_type',
        'quantity',
        'unit_price',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'inventory_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'float',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the inventory item that owns this movement.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ZraInventory::class, 'inventory_id');
    }

    /**
     * Scope for positive movements (stock added)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePositive($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope for negative movements (stock removed)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNegative($query)
    {
        return $query->where('quantity', '<', 0);
    }

    /**
     * Scope for movements of a specific type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope for movements in a date range
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $start
     * @param string $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Get the total value of this movement
     *
     * @return float
     */
    public function getTotalValueAttribute(): float
    {
        return abs($this->quantity) * $this->unit_price;
    }
}
