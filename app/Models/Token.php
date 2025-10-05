<?php

namespace App\Models;

use App\Enums\TokenType;
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
            'token_type' => TokenType::class,
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

    public function scopeOfType($query, TokenType $type)
    {
        return $query->where('token_type', $type);
    }

    public function isValid(): bool
    {
        return !$this->is_revoked
            && !$this->is_expired
            && $this->expires_at > now();
    }

    public function invalidate(): void
    {
        $this->update([
            'is_revoked' => true,
            'is_expired' => true,
        ]);
    }
}
