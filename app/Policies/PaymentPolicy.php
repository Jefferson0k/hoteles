<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermissionTo('view reportes ingresos de habitacion');
    }

    public function view(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('view payments') ||
               $user->branches()->where('branches.id', $payment->booking->room->floor->branch_id)->exists();
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create payments');
    }

    public function update(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('update payments') && 
               $payment->status === Payment::STATUS_PENDING;
    }

    public function delete(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('delete payments') && 
               $payment->status === Payment::STATUS_PENDING;
    }

    public function refund(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('refund payments') && 
               $payment->status === Payment::STATUS_COMPLETED;
    }

    public function process(User $user, Payment $payment)
    {
        return $user->hasPermissionTo('process payments') && 
               $payment->status === Payment::STATUS_PENDING;
    }
}