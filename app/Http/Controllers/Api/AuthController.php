<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ইমেইল অথবা পাসওয়ার্ড ভুল।'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'আপনার একাউন্ট সক্রিয় নয়।'], 403);
        }

        $token = $user->createToken($request->userAgent() ?? 'nextjs')->plainTextToken;

        return response()->json([
            'message' => 'লগইন সফল',
            'user'    => $this->formatUser($user),
            'token'   => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'লগআউট সফল']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'সব ডিভাইস থেকে লগআউট সফল']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'status' => $user->status,
            'avatar' => $user->avatar_url ?? null,
        ];
    }
}