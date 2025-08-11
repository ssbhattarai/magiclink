<?php

namespace Ssbhattarai\MagicLink\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $table = 'magic_links';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Generate a unique token
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Create a new magic link token
     */
    public static function createForEmail(string $email, int $expirationMinutes = 15): self
    {
        return static::create([
            'email' => $email,
            'token' => static::generateUniqueToken(),
            'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
        ]);
    }

    /**
     * Find a valid token
     */
    public static function findValidToken(string $token): ?self
    {
        return static::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): bool
    {
        return $this->update(['used_at' => Carbon::now()]);
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if token is used
     */
    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }

    /**
     * Check if token is valid (not used and not expired)
     */
    public function isValid(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    /**
     * Get the user associated with this token
     */
    public function user(): BelongsTo
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($userModel, 'email', 'email');
    }

    /**
     * Scope to get only valid tokens
     */
    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get only expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    /**
     * Scope to get only used tokens
     */
    public function scopeUsed($query)
    {
        return $query->whereNotNull('used_at');
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}
