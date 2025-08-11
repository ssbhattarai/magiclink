<?php

namespace Ssbhattarai\MagicLink\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Ssbhattarai\MagicLink\Mail\MagicLinkMail;
use Ssbhattarai\MagicLink\Models\MagicLink;

class MagicLinkService
{
    public function sendMagicLink(string $email, ?Request $request = null): void
    {
        if (config('magiclink.rate_limiting.enabled', true)) {
            $this->checkRateLimit($email, $request);
        }

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::where('email', $email)->first();

        if (! $user) {
            throw new \Exception('User not found with this email address.');
        }

        $tokenRecord = MagicLink::createForEmail(
            $email,
            config('magiclink.link_expiration', 15)
        );

        $link = URL::route('magiclink.login', $tokenRecord->token);

        Mail::to($email)->queue(new MagicLinkMail($link));

        if (config('magiclink.rate_limiting.enabled', true)) {
            $this->hitRateLimiters($email, $request);
        }
    }

    public function loginWithToken(string $token)
    {
        // Find valid token using the model
        $tokenRecord = MagicLink::findValidToken($token);

        if (! $tokenRecord) {
            throw new \Exception('Invalid or expired token.');
        }

        // Mark token as used
        $tokenRecord->markAsUsed();

        // Get user by email
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::where('email', $tokenRecord->email)->first();

        if (! $user) {
            throw new \Exception('User not found.');
        }

        return $user;
    }

    public function cleanupExpiredTokens(): int
    {
        return MagicLink::cleanupExpired();
    }

    /**
     * Check if the request has exceeded rate limits
     */
    protected function checkRateLimit(string $email, ?Request $request = null): void
    {
        $rateLimitConfig = config('magiclink.rate_limiting');

        $emailKey = 'magiclink-email:' . $email;
        if (RateLimiter::tooManyAttempts($emailKey, $rateLimitConfig['per_email']['max_attempts'])) {
            $seconds = RateLimiter::availableIn($emailKey);
            throw new \Exception("Too many magic link requests for this email. Try again in {$seconds} seconds.");
        }

        if ($request) {
            $ipKey = 'magiclink-ip:' . $request->ip();
            if (RateLimiter::tooManyAttempts($ipKey, $rateLimitConfig['per_ip']['max_attempts'])) {
                $seconds = RateLimiter::availableIn($ipKey);
                throw new \Exception("Too many magic link requests from this IP. Try again in {$seconds} seconds.");
            }
        }

        $globalKey = 'magiclink-global';
        if (RateLimiter::tooManyAttempts($globalKey, $rateLimitConfig['global']['max_attempts'])) {
            $seconds = RateLimiter::availableIn($globalKey);
            throw new \Exception("Too many magic link requests globally. Try again in {$seconds} seconds.");
        }
    }

    /**
     * Hit the rate limiters after successful request
     */
    protected function hitRateLimiters(string $email, ?Request $request = null): void
    {
        $rateLimitConfig = config('magiclink.rate_limiting');

        $emailKey = 'magiclink-email:' . $email;
        RateLimiter::hit($emailKey, $rateLimitConfig['per_email']['decay_minutes'] * 60);

        if ($request) {
            $ipKey = 'magiclink-ip:' . $request->ip();
            RateLimiter::hit($ipKey, $rateLimitConfig['per_ip']['decay_minutes'] * 60);
        }

        $globalKey = 'magiclink-global';
        RateLimiter::hit($globalKey, $rateLimitConfig['global']['decay_minutes'] * 60);
    }

    /**
     * Get remaining attempts for an email
     */
    public function getRemainingAttempts(string $email): int
    {
        if (!config('magiclink.rate_limiting.enabled', true)) {
            return PHP_INT_MAX;
        }

        $emailKey = 'magiclink-email:' . $email;
        $maxAttempts = config('magiclink.rate_limiting.per_email.max_attempts');
        
        return RateLimiter::remaining($emailKey, $maxAttempts);
    }

}
