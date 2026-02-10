<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranchCancellationPolicy extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'time_limit_hours',
        'refund_percentage',
        'no_show_charge',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'time_limit_hours' => 'integer',
        'refund_percentage' => 'decimal:2',
        'no_show_charge' => 'decimal:2',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }
}
