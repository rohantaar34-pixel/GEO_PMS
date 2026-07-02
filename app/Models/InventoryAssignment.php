<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAssignment extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'project_id',
        'transaction_id',
        'is_expense',
        'quantity_assigned',
        'unit_cost_at_assignment',
        'total_cost',
        'assigned_by',
        'notes',
    ];

    protected $casts = [
        'unit_cost_at_assignment' => 'decimal:2',
        'total_cost'              => 'decimal:2',
        'quantity_assigned'       => 'integer',
        'is_expense'              => 'boolean',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
