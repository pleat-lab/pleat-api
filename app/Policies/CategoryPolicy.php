<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Una policy concentra en un solo lugar quién puede hacer qué con un modelo,
 * en vez de repetir if de roles en cada controlador.
 * Laravel la encuentra sola por el nombre (Category -> CategoryPolicy).
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
