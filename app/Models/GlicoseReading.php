<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlicoseReading extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'profile_id',
        'glicose_value',
        'unit',
        'context',
        'measured_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
