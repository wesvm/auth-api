<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $fillable = [
        'token',
        'token_type',
        'is_revoked',
        'is_expired',
        'expires_at',
        'user_id',
    ];

    protected $hidden = [
        'token'
    ];

    protected function casts(): array
    {
        return [
            'is_revoked' => 'boolean',
            'is_expired' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now());
    }
}
