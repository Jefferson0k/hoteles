<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranchReservationSetting extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'min_advance_hours',
        'max_advance_days',
        'last_minute_surcharge_percentage',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'min_advance_hours' => 'integer',
        'max_advance_days' => 'integer',
        'last_minute_surcharge_percentage' => 'decimal:2',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }
}
