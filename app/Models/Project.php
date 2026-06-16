<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'budget', 'status'];
    
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function budgetAdditions(): HasMany
    {
        return $this->transactions()->where('type', 'budget_addition');
    }
    
    public function expenses(): HasMany
    {
        return $this->transactions()->where('type', 'expense');
    }
    
    public function getTotalBudgetAdditionsAttribute(): float
    {
        return $this->budgetAdditions()->sum('amount');
    }
    
    public function getTotalExpenseAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }
    
    public function getCurrentBudgetAttribute(): float
    {
        return $this->budget + $this->total_budget_additions - $this->total_expense;
    }
    
    public function getBalanceAttribute(): float
    {
        return $this->current_budget;
    }
    
    public function getBudgetUtilizationAttribute(): float
    {
        $totalBudget = $this->budget + $this->total_budget_additions;
        if ($totalBudget <= 0) return 0;
        return round(($this->total_expense / $totalBudget) * 100, 2);
    }
}