<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'unit',
        'unit_cost',
        'quantity',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'quantity'  => 'integer',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(InventoryAssignment::class);
    }

    public function getTotalAssignedQuantityAttribute(): int
    {
        return $this->assignments()->sum('quantity_assigned');
    }

    public function getTotalStockValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    public function getTotalAssignedValueAttribute(): float
    {
        return $this->assignments()->sum('total_cost');
    }

    public static function categoryOptions(): Collection
    {
        return static::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->pluck('category')
            ->merge(['Materials', 'Equipment', 'Supplies', 'Tools', 'Consumables'])
            ->unique(fn ($category) => mb_strtolower($category))
            ->sort()
            ->values();
    }
}
