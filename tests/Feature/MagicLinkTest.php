<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Ssbhattarai\MagicLink\Mail\MagicLinkMail;
use Ssbhattarai\MagicLink\Models\MagicLink;

uses(RefreshDatabase::class);

beforeEach(function () {
    $userModel = config('auth.providers.users.model', 'App\\Models\\User');
    $this->user = $userModel::factory()->create([
        'email' => 'test@example.com',
    ]);

    Config::set('magiclink.login_redirect', '/dashboard');
    Mail::fake();
});

describe('Magic Link Feature Tests', function () {

    it('displays magic link form', function () {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertViewIs('magiclink::magic-link')
            ->assertSee('Magic Link Login')
            ->assertSee('Enter your email address');
    });

    it('can request magic link with valid email', function () {
        $response = $this->post('/request', [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Magic link has been sent to your email!');

        // Check token was created in database
        $token = MagicLink::where('email', $this->user->email)->first();
        expect($token)->not->toBeNull()
            ->and($token->email)->toBe($this->user->email)
            ->and($token->used_at)->toBeNull();

        // Check email was queued
        Mail::assertQueued(MagicLinkMail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    });

    it('validates email field when requesting magic link', function () {
        $response = $this->post('/request', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Check no token was created
        expect(MagicLink::count())->toBe(0);

        // Check no email was sent
        Mail::assertNothingQueued();
    });

    it('requires email field when requesting magic link', function () {
        $response = $this->post('/request', []);

        $response->assertSessionHasErrors(['email']);

        // Check no token was created
        expect(MagicLink::count())->toBe(0);

        // Check no email was sent
        Mail::assertNothingQueued();
    });

    it('shows error for non-existent user email', function () {
        $response = $this->post('/request', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['email']);

        // Check no token was created
        expect(MagicLink::count())->toBe(0);

        // Check no email was sent
        Mail::assertNothingQueued();
    });

    it('can login with valid magic link token', function () {
        // Create a valid token
        $token = MagicLink::createForEmail($this->user->email);

        $response = $this->get("/login/{$token->token}");

        $response->assertRedirect('/dashboard');

        // Check user is authenticated
        expect(Auth::check())->toBeTrue()
            ->and(Auth::id())->toBe($this->user->id);

        // Check token was marked as used
        $token->refresh();
        expect($token->used_at)->not->toBeNull();
    });

    it('returns 401 for invalid token', function () {
        $response = $this->get('/login/invalid-token');

        $response->assertStatus(401);

        // Check user is not authenticated
        expect(Auth::check())->toBeFalse();
    });

    it('returns 401 for expired token', function () {
        $expiredToken = MagicLink::create([
            'email' => $this->user->email,
            'token' => MagicLink::generateUniqueToken(),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $response = $this->get("/login/{$expiredToken->token}");

        $response->assertStatus(401);

        // Check user is not authenticated
        expect(Auth::check())->toBeFalse();
    });

    it('returns 401 for used token', function () {
        $usedToken = MagicLink::createForEmail($this->user->email);
        $usedToken->markAsUsed();

        $response = $this->get("/login/{$usedToken->token}");

        $response->assertStatus(401);

        // Check user is not authenticated
        expect(Auth::check())->toBeFalse();
    });

    it('can handle multiple magic link requests for same user', function () {
        // Send first magic link
        $this->post('/request', ['email' => $this->user->email]);

        // Send second magic link
        $this->post('/request', ['email' => $this->user->email]);

        // Check both tokens exist
        $tokens = MagicLink::where('email', $this->user->email)->get();
        expect($tokens)->toHaveCount(2);

        // Check both emails were queued
        Mail::assertQueued(MagicLinkMail::class, 2);

        // Check both tokens are valid and unique
        expect($tokens->first()->token)->not->toBe($tokens->last()->token)
            ->and($tokens->first()->isValid())->toBeTrue()
            ->and($tokens->last()->isValid())->toBeTrue();
    });

    it('marks only used token when logging in', function () {
        // Create two tokens for the same user
        $token1 = MagicLink::createForEmail($this->user->email);
        $token2 = MagicLink::createForEmail($this->user->email);

        // Login with first token
        $this->get("/login/{$token1->token}");

        $token1->refresh();
        $token2->refresh();

        // Check only first token is marked as used
        expect($token1->used_at)->not->toBeNull()
            ->and($token2->used_at)->toBeNull();
    });

    it('respects login redirect configuration', function () {
        Config::set('magiclink.login_redirect', '/custom-dashboard');

        $token = MagicLink::createForEmail($this->user->email);

        $response = $this->get("/login/{$token->token}");

        $response->assertRedirect('/custom-dashboard');
    });

    it('preserves form data on validation errors', function () {
        $response = $this->post('/request', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasInput('email', 'invalid-email');
    });

    it('can login different users with their respective tokens', function () {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user2 = $userModel::factory()->create([
            'email' => 'user2@example.com',
        ]);

        $token1 = MagicLink::createForEmail($this->user->email);
        $token2 = MagicLink::createForEmail($user2->email);

        // Login with first user's token
        $this->get("/login/{$token1->token}");
        expect(Auth::id())->toBe($this->user->id);

        // Logout
        Auth::logout();

        // Login with second user's token
        $this->get("/login/{$token2->token}");
        expect(Auth::id())->toBe($user2->id);
    });

    it('generates proper magic link URLs in emails', function () {
        $this->post('/request', ['email' => $this->user->email]);

        $token = MagicLink::where('email', $this->user->email)->first();
        $expectedUrl = URL::route('magiclink.login', $token->token);

        Mail::assertQueued(MagicLinkMail::class, function ($mail) use ($expectedUrl) {
            return $mail->link === $expectedUrl;
        });
    });
});
