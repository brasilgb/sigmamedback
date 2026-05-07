<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $appends = [
        'display_status',
    ];

    protected $fillable = [
        'tenant_id',
        'external_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'plan_type',
        'qr_code',
        'qr_code_base64',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getDisplayStatusAttribute(): string
    {
        if (
            $this->status === 'pending'
            && $this->expires_at
            && $this->expires_at->isPast()
        ) {
            return 'expired';
        }

        return $this->status;
    }
}
