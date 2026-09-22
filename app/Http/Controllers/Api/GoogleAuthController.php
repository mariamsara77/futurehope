<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    private const STATE_TTL_MINUTES = 10;
    private const CODE_TTL_MINUTES = 2;

    public function redirect(): RedirectResponse
    {
        $state = Str::random(96);

        Cache::put(
            $this->stateKey($state),
            true,
            now()->addMinutes(self::STATE_TTL_MINUTES)
        );

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontend = rtrim((string) config('services.frontend_url'), '/');
        $failureUrl = $frontend . '/auth/callback/google?error=google_login_failed';

        if (!$request->filled('state') || !Cache::pull($this->stateKey((string) $request->string('state')))) {
            return redirect()->away($failureUrl . '&reason=invalid_state');
        }

        if (!$request->filled('code')) {
            return redirect()->away($failureUrl . '&reason=missing_code');
        }

        $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'code' => (string) $request->string('code'),
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful() || !$tokenResponse->json('access_token')) {
            report(new \RuntimeException('Google token exchange failed: ' . $tokenResponse->body()));

            return redirect()->away($failureUrl . '&reason=token_exchange');
        }

        $googleResponse = Http::withToken($tokenResponse->json('access_token'))
            ->timeout(15)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (!$googleResponse->successful()) {
            report(new \RuntimeException('Google user info request failed: ' . $googleResponse->body()));

            return redirect()->away($failureUrl . '&reason=user_info');
        }

        $google = $googleResponse->json();
        $email = strtolower(trim((string) ($google['email'] ?? '')));

        if (!$email || empty($google['sub']) || ($google['email_verified'] ?? false) !== true) {
            return redirect()->away($failureUrl . '&reason=unverified_email');
        }

        $user = User::where('google_id', (string) $google['sub'])->first();

        if (!$user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                if ($user->status !== 'active') {
                    return redirect()->away($failureUrl . '&reason=inactive_account');
                }

                $user->forceFill([
                    'google_id' => (string) $google['sub'],
                ])->save();
            } else {
                $user = User::create([
                    'name' => trim((string) ($google['name'] ?? strstr($email, '@', true))),
                    'email' => $email,
                    'password' => null,
                    'google_id' => (string) $google['sub'],
                    'status' => 'active',
                ]);
            }
        }

        if ($user->status !== 'active') {
            return redirect()->away($failureUrl . '&reason=inactive_account');
        }

        // Google avatar is a fallback. A user-uploaded Media Library
        // avatar always wins over this URL.
        if (!$user->hasMedia('avatar') && !empty($google['picture'])) {
            $user->forceFill(['avatar' => (string) $google['picture']])->save();
        }

        $oneTimeCode = Str::random(128);

        Cache::put(
            $this->codeKey($oneTimeCode),
            $user->id,
            now()->addMinutes(self::CODE_TTL_MINUTES)
        );

        return redirect()->away(
            $frontend . '/auth/callback/google#code=' . urlencode($oneTimeCode)
        );
    }

    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:128'],
        ]);

        $userId = Cache::pull($this->codeKey($data['code']));

        if (!$userId) {
            return response()->json([
                'message' => 'Google login code invalid or expired. আবার Google দিয়ে লগইন করুন।',
            ], 422);
        }

        $user = User::find($userId);

        if (!$user || $user->status !== 'active') {
            return response()->json([
                'message' => 'আপনার account এখন active নয়।',
            ], 403);
        }

        $token = $user->createToken(
            $request->userAgent() ?: 'google-frontend'
        )->plainTextToken;

        return response()->json([
            'message' => 'Google login সফল',
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'avatar' => $user->avatar_url,
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }

    private function stateKey(string $state): string
    {
        return 'futurehope:google:state:' . hash('sha256', $state);
    }

    private function codeKey(string $code): string
    {
        return 'futurehope:google:code:' . hash('sha256', $code);
    }
}
