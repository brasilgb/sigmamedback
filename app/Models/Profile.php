<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    use BelongsToTenant, HasFactory;

    protected $appends = [
        'avatar_url',
    ];

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'name',
        'age',
        'sex',
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

    protected static function booted(): void
    {
        static::updating(function (Profile $profile): void {
            if ($profile->isDirty('photo_path') && $profile->getOriginal('photo_path')) {
                Storage::disk('public')->delete($profile->getOriginal('photo_path'));
            }
        });

        static::deleting(function (Profile $profile): void {
            if ($profile->photo_path) {
                Storage::disk('public')->delete($profile->photo_path);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return rtrim(config('app.url'), '/').'/'.ltrim($this->photo_path, '/');
    }

    public function bloodPressureReadings()
    {
        return $this->hasMany(BloodPressureReading::class);
    }
}
