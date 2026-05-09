<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'profile_id',
        'name',
        'dosage',
        'instructions',
        'active',
        'scheduled_time',
        'dose_interval',
        'reminder_enabled',
        'repeat_reminder_every_five_minutes',
        'reminder_minutes_before',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'reminder_enabled' => 'boolean',
        'repeat_reminder_every_five_minutes' => 'boolean',
        'scheduled_time' => 'datetime:Y-m-d H:i:s',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function logs()
    {
        return $this->hasMany(MedicationLog::class);
    }
}
