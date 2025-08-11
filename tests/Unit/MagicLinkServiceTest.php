<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Ssbhattarai\MagicLink\Mail\MagicLinkMail;
use Ssbhattarai\MagicLink\Models\MagicLink;
use Ssbhattarai\MagicLink\Services\MagicLinkService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new MagicLinkService;
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Config::set('magiclink.link_expiration', 15);
    Mail::fake();
});

describe('MagicLinkService', function () {

    it('can send magic link for existing user', function () {
        $this->service->sendMagicLink($this->user->email);

        // Check token was created
        $token = MagicLink::where('email', $this->user->email)->first();
        expect($token)->not->toBeNull()
            ->and($token->email)->toBe($this->user->email)
            ->and($token->token)->toHaveLength(64)
            ->and($token->used_at)->toBeNull();

        // Check email was queued
        Mail::assertQueued(MagicLinkMail::class, function ($mail) use ($token) {
            $expectedLink = URL::route('magiclink.login', $token->token);

            return $mail->hasTo($this->user->email) && $mail->link === $expectedLink;
        });
    });

    it('throws exception for non-existent user', function () {
        expect(fn () => $this->service->sendMagicLink('nonexistent@example.com'))
            ->toThrow(Exception::class, 'User not found with this email address.');

        // Check no token was created
        expect(MagicLink::count())->toBe(0);

        // Check no email was sent
        Mail::assertNothingQueued();
    });

    it('respects link expiration configuration', function () {
        Config::set('magiclink.link_expiration', 30);

        $beforeSending = Carbon::now();
        $this->service->sendMagicLink($this->user->email);
        $afterSending = Carbon::now();

        $token = MagicLink::where('email', $this->user->email)->first();
        $expectedExpiration = $beforeSending->copy()->addMinutes(30);

        expect($token->expires_at)->toBeAfter($expectedExpiration->subSecond())
            ->and($token->expires_at)->toBeBefore($afterSending->addMinutes(30)->addSecond());
    });

    it('can login with valid token', function () {
        // Create a valid token
        $token = MagicLink::createForEmail($this->user->email);

        $loggedInUser = $this->service->loginWithToken($token->token);

        expect($loggedInUser)->toBeInstanceOf(User::class)
            ->and($loggedInUser->id)->toBe($this->user->id)
            ->and($loggedInUser->email)->toBe($this->user->email);

        // Check token was marked as used
        $token->refresh();
        expect($token->used_at)->not->toBeNull();
    });

    it('throws exception for invalid token', function () {
        expect(fn () => $this->service->loginWithToken('invalid-token'))
            ->toThrow(Exception::class, 'Invalid or expired token.');
    });

    it('throws exception for expired token', function () {
        $expiredToken = MagicLink::create([
            'email' => $this->user->email,
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        expect(fn () => $this->service->loginWithToken($expiredToken->token))
            ->toThrow(Exception::class, 'Invalid or expired token.');
    });

    it('throws exception for used token', function () {
        $usedToken = MagicLink::createForEmail($this->user->email);
        $usedToken->markAsUsed();

        expect(fn () => $this->service->loginWithToken($usedToken->token))
            ->toThrow(Exception::class, 'Invalid or expired token.');
    });

    it('throws exception when user not found for valid token', function () {
        // Create token for user
        $token = MagicLink::createForEmail($this->user->email);

        // Delete the user
        $this->user->delete();

        expect(fn () => $this->service->loginWithToken($token->token))
            ->toThrow(Exception::class, 'User not found.');
    });

    it('can cleanup expired tokens', function () {
        // Create valid tokens
        MagicLink::createForEmail('valid1@example.com');
        MagicLink::createForEmail('valid2@example.com');

        // Create expired tokens
        MagicLink::create([
            'email' => 'expired1@example.com',
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);
        MagicLink::create([
            'email' => 'expired2@example.com',
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subHours(1),
        ]);

        expect(MagicLink::count())->toBe(4);

        $deletedCount = $this->service->cleanupExpiredTokens();

        expect($deletedCount)->toBe(2)
            ->and(MagicLink::count())->toBe(2);
    });

    it('generates unique tokens for multiple requests', function () {
        $this->service->sendMagicLink($this->user->email);
        $this->service->sendMagicLink($this->user->email);

        $tokens = MagicLink::where('email', $this->user->email)->get();

        expect($tokens)->toHaveCount(2)
            ->and($tokens->first()->token)->not->toBe($tokens->last()->token);
    });

    it('uses correct user model from config', function () {
        // Test with default user model
        $this->service->sendMagicLink($this->user->email);

        $loggedInUser = $this->service->loginWithToken(
            MagicLink::where('email', $this->user->email)->first()->token
        );

        expect($loggedInUser)->toBeInstanceOf(User::class);
    });
});
