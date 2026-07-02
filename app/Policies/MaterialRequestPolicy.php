<?php

namespace App\Policies;

use App\Models\MaterialRequest;
use App\Models\User;

class MaterialRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageOperations();
    }

    public function create(User $user): bool
    {
        return $user->isEmployee();
    }

    public function view(User $user, MaterialRequest $materialRequest): bool
    {
        return $user->canManageOperations() || $materialRequest->user_id === $user->id;
    }

    public function approve(User $user, MaterialRequest $materialRequest): bool
    {
        return $user->canManageOperations();
    }

    public function reject(User $user, MaterialRequest $materialRequest): bool
    {
        return $user->canManageOperations();
    }
}
