<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestAudit extends Model
{
    protected $fillable = [
        'material_request_id',
        'user_id',
        'role',
        'project_id',
        'material_name',
        'quantity',
        'action',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
