<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_a_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Member',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'avatar', 'roles']]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_google_only_account_cannot_use_password_login(): void
    {
        User::create([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'password' => null,
            'google_id' => 'google-sub-1',
            'status' => 'active',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'google@example.com',
            'password' => 'Password123!',
        ])->assertStatus(422);
    }

    public function test_google_oauth_creates_user_and_exchanges_a_one_time_code(): void
    {
        config()->set('services.google.client_id', 'test-client');
        config()->set('services.google.client_secret', 'test-secret');
        config()->set('services.google.redirect', 'http://localhost:8000/api/auth/google/callback');
        config()->set('services.frontend_url', 'http://localhost:3000');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub-123',
                'email' => 'google@example.com',
                'email_verified' => true,
                'name' => 'Google Person',
                'picture' => 'https://lh3.googleusercontent.com/avatar-test',
            ], 200),
        ]);

        $redirect = $this->get('/api/auth/google/redirect')->assertRedirect();
        $googleLocation = (string) $redirect->headers->get('Location');
        parse_str((string) parse_url($googleLocation, PHP_URL_QUERY), $googleQuery);
        $state = (string) ($googleQuery['state'] ?? '');

        $this->assertSame(96, strlen($state));

        $callback = $this->get('/api/auth/google/callback?code=google-code&state=' . urlencode($state));

        $callback->assertRedirect();
        $location = (string) $callback->headers->get('Location');

        $this->assertStringContainsString('http://localhost:3000/auth/callback/google#code=', $location);
        $this->assertDatabaseHas('users', [
            'email' => 'google@example.com',
            'google_id' => 'google-sub-123',
            'status' => 'active',
        ]);

        parse_str(parse_url($location, PHP_URL_FRAGMENT) ?: '', $fragment);
        $this->assertArrayHasKey('code', $fragment);

        $exchange = $this->postJson('/api/auth/google/exchange', [
            'code' => $fragment['code'],
        ]);

        $exchange
            ->assertOk()
            ->assertJsonPath('user.email', 'google@example.com')
            ->assertJsonStructure(['token', 'user']);

        $this->assertNotEmpty($exchange->json('token'));

        $this->postJson('/api/auth/google/exchange', [
            'code' => $fragment['code'],
        ])->assertStatus(422);
    }
}
