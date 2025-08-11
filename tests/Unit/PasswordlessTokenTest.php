<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ssbhattarai\MagicLink\Models\MagicLink;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->email = 'test@example.com';
});

describe('MagicLink Model', function () {

    it('can generate a unique token', function () {
        $token1 = MagicLink::generateUniqueToken();
        $token2 = MagicLink::generateUniqueToken();

        expect($token1)->toBeString()
            ->and($token1)->toHaveLength(64)
            ->and($token1)->not->toBe($token2);
    });

    it('can create a token for email', function () {
        $token = MagicLink::createForEmail($this->email, 30);

        expect($token)->toBeInstanceOf(MagicLink::class)
            ->and($token->email)->toBe($this->email)
            ->and($token->token)->toHaveLength(64)
            ->and($token->expires_at)->toBeInstanceOf(Carbon::class)
            ->and($token->used_at)->toBeNull();
    });

    it('sets correct expiration time when creating token', function () {
        $minutes = 30;
        $beforeCreation = Carbon::now();

        $token = MagicLink::createForEmail($this->email, $minutes);

        $afterCreation = Carbon::now();
        $expectedExpiration = $beforeCreation->copy()->addMinutes($minutes);

        expect($token->expires_at)->toBeAfter($expectedExpiration->subSecond())
            ->and($token->expires_at)->toBeBefore($afterCreation->addMinutes($minutes)->addSecond());
    });

    it('can find a valid token', function () {
        $token = MagicLink::createForEmail($this->email);

        $foundToken = MagicLink::findValidToken($token->token);

        expect($foundToken)->not->toBeNull()
            ->and($foundToken->id)->toBe($token->id);
    });

    it('returns null when finding invalid token', function () {
        $foundToken = MagicLink::findValidToken('invalid-token');

        expect($foundToken)->toBeNull();
    });

    it('returns null when finding expired token', function () {
        $token = MagicLink::create([
            'email' => $this->email,
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1), // Expired
        ]);

        $foundToken = MagicLink::findValidToken($token->token);

        expect($foundToken)->toBeNull();
    });

    it('returns null when finding used token', function () {
        $token = MagicLink::createForEmail($this->email);
        $token->markAsUsed();

        $foundToken = MagicLink::findValidToken($token->token);

        expect($foundToken)->toBeNull();
    });

    it('can mark token as used', function () {
        $token = MagicLink::createForEmail($this->email);

        expect($token->used_at)->toBeNull();

        $result = $token->markAsUsed();
        $token->refresh();

        expect($result)->toBeTrue()
            ->and($token->used_at)->toBeInstanceOf(Carbon::class)
            ->and($token->used_at)->toBeBefore(Carbon::now()->addSecond());
    });

    it('correctly identifies expired tokens', function () {
        $validToken = MagicLink::createForEmail($this->email, 30);
        $expiredToken = MagicLink::create([
            'email' => $this->email,
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        expect($validToken->isExpired())->toBeFalse()
            ->and($expiredToken->isExpired())->toBeTrue();
    });

    it('correctly identifies used tokens', function () {
        $unusedToken = MagicLink::createForEmail($this->email);
        $usedToken = MagicLink::createForEmail($this->email);
        $usedToken->markAsUsed();

        expect($unusedToken->isUsed())->toBeFalse()
            ->and($usedToken->isUsed())->toBeTrue();
    });

    it('correctly identifies valid tokens', function () {
        $validToken = MagicLink::createForEmail($this->email);
        $expiredToken = MagicLink::create([
            'email' => $this->email,
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);
        $usedToken = MagicLink::createForEmail($this->email);
        $usedToken->markAsUsed();

        expect($validToken->isValid())->toBeTrue()
            ->and($expiredToken->isValid())->toBeFalse()
            ->and($usedToken->isValid())->toBeFalse();
    });

    it('can clean up expired tokens', function () {
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

        $deletedCount = MagicLink::cleanupExpired();

        expect($deletedCount)->toBe(2)
            ->and(MagicLink::count())->toBe(2);
    });

    it('has working scopes', function () {
        // Create various tokens
        $validToken = MagicLink::createForEmail('valid@example.com');

        $expiredToken = MagicLink::create([
            'email' => 'expired@example.com',
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $usedToken = MagicLink::createForEmail('used@example.com');
        $usedToken->markAsUsed();

        expect(MagicLink::valid()->count())->toBe(1)
            ->and(MagicLink::expired()->count())->toBe(1)
            ->and(MagicLink::used()->count())->toBe(1);
    });
});
