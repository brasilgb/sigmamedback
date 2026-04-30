<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'owner_id',
        'account_usage',
        'sync_enabled',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps()
            ->withPivot('role');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}
