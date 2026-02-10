<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranchCheckinSetting extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'checkin_time',
        'checkout_time',
        'early_checkin_cost',
        'late_checkout_cost',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'early_checkin_cost' => 'decimal:2',
        'late_checkout_cost' => 'decimal:2',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }
}
