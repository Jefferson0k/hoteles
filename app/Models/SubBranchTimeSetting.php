<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranchTimeSetting extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes,  HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'max_allowed_time',
        'extra_tolerance',
        'apply_tolerance',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'max_allowed_time' => 'integer',
        'extra_tolerance' => 'integer',
        'apply_tolerance' => 'boolean',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }
}
