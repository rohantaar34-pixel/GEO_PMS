<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    public const COMMITMENT_RESERVED = 'reserved';
    public const COMMITMENT_CONVERTED = 'converted';
    public const COMMITMENT_RELEASED = 'released';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_WAITING_PROCUREMENT = 'waiting_for_procurement';
    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'request_number',
        'project_id',
        'user_id',
        'inventory_item_id',
        'material_name',
        'material_category',
        'quantity',
        'approved_quantity',
        'unit',
        'purpose',
        'request_note',
        'date_requested',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'procurement_note',
        'estimated_unit_cost',
        'estimated_total_cost',
        'actual_total_cost',
        'budget_commitment_status',
        'budget_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'approved_quantity' => 'integer',
            'estimated_unit_cost' => 'decimal:2',
            'estimated_total_cost' => 'decimal:2',
            'actual_total_cost' => 'decimal:2',
            'date_requested' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function budgetTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'budget_transaction_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MaterialRequestAudit::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
