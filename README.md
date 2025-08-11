# 🔗 Laravel Magic Link

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ssbhattarai/magiclink.svg?style=flat-square)](https://packagist.org/packages/ssbhattarai/magiclink)
[![Total Downloads](https://img.shields.io/packagist/dt/ssbhattarai/magiclink.svg?style=flat-square)](https://packagist.org/packages/ssbhattarai/magiclink)
[![License](https://img.shields.io/github/license/ssbhattarai/magiclink.svg?style=flat-square)](LICENSE.md)
[![Tests](https://img.shields.io/github/actions/workflow/status/ssbhattarai/magiclink/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ssbhattarai/magiclink/actions)

**A passwordless login system for Laravel that allows users to log in using magic links sent to their email.**

> **Zero passwords, maximum security!** Send secure, time-limited login links directly to users' inboxes.

---

## Features

- 🔐 **Passwordless Authentication** - Secure login without passwords
- ⏰ **Time-Limited Links** - Configurable expiration times for security
- 📧 **Beautiful Email Templates** - Professional, responsive email design
- 🎨 **Customizable Views** - Easily customize email and request form templates
- 🔄 **Queue Support** - Send emails asynchronously for better performance
- 🧹 **Auto Cleanup** - Automatic expired token cleanup
- 🛠️ **Artisan Commands** - Built-in commands for diagnostics and maintenance
- 🔍 **Comprehensive Testing** - 100% test coverage for reliability
- 📱 **Mobile-Friendly** - Responsive design for all devices

---

## 📋 Requirements

| Laravel Version | PHP Version | Package Version |
|----------------|-------------|-----------------|
| 10.x - 11.x    | >= 8.2      | ^1.0           |
| 9.x            | >= 8.1      | Contact author  |

---

## 📦 Installation

### 1. Install via Composer

```bash
composer require ssbhattarai/magiclink
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Ssbhattarai\MagicLink\MagicLinkServiceProvider" --tag="magiclink-config"
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Optional: Publish Views (for customization)

```bash
php artisan vendor:publish --provider="Ssbhattarai\MagicLink\MagicLinkServiceProvider" --tag="magiclink-views"
```

### 5. Optional: Publish Migrations (for customization)

```bash
php artisan vendor:publish --provider="Ssbhattarai\MagicLink\MagicLinkServiceProvider" --tag="magiclink-migrations"
```

---

## Configuration

The configuration file `config/magiclink.php` provides several customization options:

```php
<?php

return [
    // Link expiration time in minutes
    'link_expiration' => 15,
    
    // Redirect URL after successful login
    'login_redirect' => '/dashboard',
    
    // Email subject line
    'email_subject' => 'Your Magic Login Link',
    
    // Route prefix for magic link endpoints
    'prefix' => 'magic-link',
    
    // Middleware applied to magic link routes
    'middleware' => ['web'],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for magic link requests to prevent abuse.
    | You can set different limits for different scenarios.
    |
    */
    'rate_limiting' => [
        // Rate limit for magic link requests per email address
        'per_email' => [
            'max_attempts' => 3,        // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Rate limit for magic link requests per IP address
        'per_ip' => [
            'max_attempts' => 10,       // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Global rate limit for all magic link requests
        'global' => [
            'max_attempts' => 100,      // Maximum attempts
            'decay_minutes' => 60,      // Time window in minutes
        ],
        
        // Enable/disable rate limiting
        'enabled' => true,
    ],
];
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `link_expiration` | integer | 15 | Link validity in minutes |
| `login_redirect` | string | '/dashboard' | Post-login redirect URL |
| `email_subject` | string | 'Your Magic Login Link' | Email subject line |
| `prefix` | string | 'magic-link' | Route prefix |
| `middleware` | array | ['web'] | Applied middleware |
| `rate_limiting.enabled` | boolean | true | Enable/disable rate limiting |
| `rate_limiting.per_email.max_attempts` | integer | 3 | Max attempts per email |
| `rate_limiting.per_email.decay_minutes` | integer | 60 | Rate limit window for email |
| `rate_limiting.per_ip.max_attempts` | integer | 10 | Max attempts per IP |
| `rate_limiting.per_ip.decay_minutes` | integer | 60 | Rate limit window for IP |
| `rate_limiting.global.max_attempts` | integer | 100 | Global max attempts |
| `rate_limiting.global.decay_minutes` | integer | 60 | Global rate limit window |

---

## Usage

### Basic Usage

#### 1. Request a Magic Link

Users can request a magic link by visiting the magic link form:

```
GET /magic-link
```

Or programmatically:

```php
use Ssbhattarai\MagicLink\Services\MagicLinkService;

$magicLinkService = app(MagicLinkService::class);

try {
    $magicLinkService->sendMagicLink('user@example.com');
    // Success: Magic link sent
} catch (\Exception $e) {
    // Handle error: User not found, rate limited, or email failed
    if (str_contains($e->getMessage(), 'Too many')) {
        // Rate limit exceeded
        return back()->withErrors(['email' => 'Rate limit exceeded. Please try again later.']);
    }
    return back()->withErrors(['email' => $e->getMessage()]);
}
```

### Rate Limiting Usage

Check remaining attempts for an email:

```php
use Ssbhattarai\MagicLink\Services\MagicLinkService;

$service = app(MagicLinkService::class);
$remainingAttempts = $service->getRemainingAttempts('user@example.com');

if ($remainingAttempts <= 0) {
    return response()->json(['error' => 'Rate limit exceeded'], 429);
}
```

Clear rate limits programmatically:

```php
use Ssbhattarai\MagicLink\Services\MagicLinkService;

$service = app(MagicLinkService::class);
$service->clearRateLimit('user@example.com', $request);
```

#### 2. Login via Magic Link

When users click the magic link from their email, they're automatically logged in:

```
GET /magic-link/login/{token}
```

### Available Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/magic-link` | `magiclink.request` | Show magic link request form |
| POST | `/magic-link/request` | `magiclink.request` | Process magic link request |
| GET | `/magic-link/login/{token}` | `magiclink.login` | Login with magic link token |

---

## Customization

### Custom Email Template

After publishing views, customize the email template at:

```
resources/views/vendor/magiclink/email.blade.php
```

**Example Custom Template:**

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome Back!</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Welcome Back! 👋</h1>
        <p>Click the button below to access your account:</p>
        
        <a href="{{ $link }}" 
           style="background: #4F46E5; color: white; padding: 12px 24px; 
                  text-decoration: none; border-radius: 6px; display: inline-block;">
            Access My Account
        </a>
        
        <p><small>This link expires in {{ config('magiclink.link_expiration') }} minutes.</small></p>
    </div>
</body>
</html>
```

### Custom Request Form

Customize the magic link request form at:

```
resources/views/vendor/magiclink/magic-link.blade.php
```

### Using Your Own Controller

Create a custom controller to handle magic link requests:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ssbhattarai\MagicLink\Services\MagicLinkService;

class AuthController extends Controller
{
    public function requestMagicLink(Request $request, MagicLinkService $service)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        try {
            $service->sendMagicLink($request->email);
            return response()->json(['message' => 'Magic link sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

---

## Database Schema

The package creates a `magic_links` table with the following structure:

```php
Schema::create('magic_links', function (Blueprint $table) {
    $table->id();
    $table->string('email')->index();
    $table->string('token')->unique();
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamps();
    
    $table->index(['email', 'expires_at'], 'idx_email_expires');
    $table->index('token', 'idx_token');
});
```

### Fields Description

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Primary key |
| `email` | string | User's email address |
| `token` | string | Unique magic link token |
| `expires_at` | timestamp | Token expiration time |
| `used_at` | timestamp | When token was used (nullable) |
| `created_at` | timestamp | Token creation time |
| `updated_at` | timestamp | Last update time |

---

## Artisan Commands

### Check Installation

Verify that the package is properly installed and configured:

```bash
php artisan magiclink:check
```

**Output Example:**
```
Checking MagicLink package installation...
✅ Routes are registered:
   POST magic-link/request (magiclink.request)
   GET magic-link/login/{token} (magiclink.login)
✅ Configuration loaded
✅ User model found: App\Models\User
✅ Database table "magic_links" exists
   Total tokens: 15
   Expired tokens: 3
⚠️ Using sync queue driver (emails sent immediately)
```

### Cleanup Expired Tokens

Remove expired tokens from the database:

```bash
php artisan magiclink:cleanup
```

### Clear Rate Limits

Clear rate limits for magic link requests:

```bash
# Clear rate limit for specific email
php artisan magiclink:clear-rate-limit user@example.com

# Clear all rate limits
php artisan magiclink:clear-rate-limit
```

### Rate Limiting Status

Check the current rate limiting status:

```bash
php artisan magiclink:rate-limit
```

**Output Example:**
```
Checking MagicLink rate limiting status...
✅ Rate limiting is enabled
✅ Global rate limit: 100 attempts, 60 minutes
✅ Email rate limit: 3 attempts, 60 minutes
✅ IP rate limit: 10 attempts, 60 minutes
```

---

## Troubleshooting

### 404 Errors

If you're getting 404 errors after installation:

1. **Clear caches:**
   ```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Verify routes:**
   ```bash
   php artisan route:list | grep magic-link
   ```

3. **Check User model:**
   Ensure your User model exists at `App\Models\User`

### Email Not Sending

1. **Check queue configuration:**
   ```bash
   php artisan queue:work
   ```

2. **Verify mail configuration in `.env`:**
   ```env
    MAIL_MAILER=smtp
    MAIL_SCHEME=null
    MAIL_HOST=sandbox.smtp.mailtrap.io // you can add yours
    MAIL_PORT=2525
    MAIL_USERNAME=usermae
    MAIL_PASSWORD=password
    MAIL_FROM_ADDRESS="no-reply@example.com"
    MAIL_FROM_NAME="${APP_NAME}"
   ```

3. **Test email configuration:**
   ```bash
   php artisan tinker
   Mail::raw('Test', function($m) { $m->to('test@example.com'); });
   ```

### Token Validation Issues

1. **Check system time:**
   Ensure server time is correct for expiration validation

2. **Verify token in database:**
   ```sql
   SELECT * FROM magic_links WHERE token = 'your_token';
   ```

---

## Testing

### Run Package Tests

```bash
composer test
```

### Individual Test Commands

```bash
# Linting
composer test:lint

# Type coverage
composer test:type-coverage

# Unit tests with coverage
composer test:unit

# Static analysis
composer test:types
```

### Example Test Case

```php
<?php

use Ssbhattarai\MagicLink\Services\MagicLinkService;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    public function test_can_send_magic_link()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $service = app(MagicLinkService::class);
        $service->sendMagicLink('test@example.com');
        
        $this->assertDatabaseHas('magic_links', [
            'email' => 'test@example.com'
        ]);
    }
    
    public function test_can_login_with_valid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $service = app(MagicLinkService::class);
        $service->sendMagicLink('test@example.com');
        
        $token = MagicLink::where('email', 'test@example.com')->first()->token;
        
        $response = $this->get(route('magiclink.login', $token));
        
        $response->assertRedirect(config('magiclink.login_redirect'));
        $this->assertAuthenticatedAs($user);
    }
}
```

---

## Security Considerations

### Best Practices

1. **Use HTTPS:** Always use HTTPS in production to protect magic links in transit
2. **Short Expiration:** Keep link expiration times short (10/15 minutes recommended)
3. **Single Use:** Tokens are automatically invalidated after use
4. **Email Validation:** Verify email addresses belong to real users
5. **Rate Limiting:** Implement rate limiting on magic link requests

### Implementation Example

```php
// In your RouteServiceProvider or controller
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/magic-link/request', [MagicLinkController::class, 'requestLink']);
});
```

---

## Contributing

We welcome contributions! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/amazing-feature`
3. **Run tests:** `composer test`
4. **Commit changes:** `git commit -m 'Add amazing feature'`
5. **Push to branch:** `git push origin feature/amazing-feature`
6. **Open a Pull Request**

### Development Setup

```bash
git clone https://github.com/ssbhattarai/magiclink.git
cd magiclink
composer install
composer test
```

---

## API Reference

### MagicLinkService

#### `sendMagicLink(string $email): void`

Sends a magic link to the specified email address.

**Parameters:**
- `$email` (string): User's email address

**Throws:**
- `\Exception`: If user not found

**Example:**
```php
$service = app(MagicLinkService::class);
$service->sendMagicLink('user@example.com');
```

#### `loginWithToken(string $token): User`

Authenticates user with magic link token.

**Parameters:**
- `$token` (string): Magic link token

**Returns:**
- `User`: Authenticated user instance

**Throws:**
- `\Exception`: If token invalid or expired

#### `cleanupExpiredTokens(): int`

Removes expired tokens from database.

**Returns:**
- `int`: Number of deleted tokens

### MagicLink Model

#### Scopes

```php
// Get valid tokens
MagicLink::valid()->get();

// Get expired tokens
MagicLink::expired()->get();

// Get used tokens
MagicLink::used()->get();
```

#### Methods

```php
$token = MagicLink::findValidToken('token_string');
$token->markAsUsed();
$token->isValid(); // bool
$token->isExpired(); // bool
$token->isUsed(); // bool
```

---

## Changelog

### v1.0.0 (Latest)
- ✨ Initial release
- 🔐 Passwordless authentication system
- 📧 Beautiful email templates
- 🛠️ Artisan commands for management
- 🧪 100% test coverage

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

---

## Author

**Shyam Sundar Bhattarai**
- Email: [nascentsurya@gmail.com](mailto:nascentsurya@gmail.com)
- GitHub: [@ssbhattarai](https://github.com/ssbhattarai)

---

## Acknowledgments

- Laravel team for the amazing framework
- All contributors who help improve this package
- The open-source community for inspiration and feedback

---

## Support

If you discover any security vulnerabilities, please send an e-mail to [nascentsurya@gmail.com](mailto:nascentsurya@gmail.com).

For general questions and support:
- 📧 Email: [nascentsurya@gmail.com](mailto:nascentsurya@gmail.com)
- 🐛 Issues: [GitHub Issues](https://github.com/ssbhattarai/magiclink/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/ssbhattarai/magiclink/discussions)

---

<div align="center">

**Made with ❤️ for the Laravel community**

[⭐ Star this repo](https://github.com/ssbhattarai/magiclink) if it helped you!

</div>
