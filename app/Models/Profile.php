<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'name',
        'age',
        'height',
        'target_weight',
        'has_diabetes',
        'has_hypertension',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'has_diabetes' => 'boolean',
        'has_hypertension' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bloodPressureReadings()
    {
        return $this->hasMany(BloodPressureReading::class);
    }
}
