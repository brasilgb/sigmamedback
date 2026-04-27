<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicationLog extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'medication_id',
        'profile_id',
        'taken_at',
        'notes',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
