<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view products');
    }

    public function view(User $user, Product $product)
    {
        return $user->hasPermissionTo('view products');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create products');
    }

    public function update(User $user, Product $product)
    {
        return $user->hasPermissionTo('update products') ||
               ($user->hasPermissionTo('update own products') && $user->id === $product->created_by);
    }

    public function delete(User $user, Product $product)
    {
        return $user->hasPermissionTo('delete products') ||
               ($user->hasPermissionTo('delete own products') && $user->id === $product->created_by);
    }

    public function restore(User $user, Product $product)
    {
        return $user->hasPermissionTo('restore products');
    }

    public function forceDelete(User $user, Product $product)
    {
        return $user->hasPermissionTo('force delete products');
    }

    public function reportIngresoProducto(User $user)
    {
        return $user->hasPermissionTo('view report de ingreso producto');
    }
}
