<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class SubBranchNotificationSetting extends Model implements Auditable
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'sub_branch_id',
        'reservation_reminder_active',
        'reminder_hours_before',
        'excess_alert_active',
        'confirmation_email_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'reservation_reminder_active' => 'boolean',
        'reminder_hours_before' => 'integer',
        'excess_alert_active' => 'boolean',
        'confirmation_email_active' => 'boolean',
    ];

    // Relaciones
    public function subBranch()
    {
        return $this->belongsTo(SubBranch::class);
    }
}
