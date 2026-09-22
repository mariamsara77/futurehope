<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'status' => 'active',
        ]);

        $this->ensureMemberRole($user);

        if ($request->hasFile('image')) {
            $this->storeAvatar($user, $request);
            $user->refresh();
        }

        $token = $user->createToken(
            $request->userAgent() ?: 'frontend'
        )->plainTextToken;

        return response()->json([
            'message' => 'রেজিস্ট্রেশন সফল। এখন আপনার প্রোফাইল পূরণ করুন।',
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where(
            'email',
            strtolower(trim($credentials['email']))
        )->first();

        if (
            !$user ||
            !$user->password ||
            !Hash::check($credentials['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => ['ইমেইল অথবা পাসওয়ার্ড ভুল।'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'আপনার একাউন্ট সক্রিয় নয়।',
            ], 403);
        }

        $this->ensureMemberRole($user);

        $token = $user->createToken(
            $request->userAgent() ?: 'frontend'
        )->plainTextToken;

        return response()->json([
            'message' => 'লগইন সফল',
            'user' => $this->formatUser($user),
            'token' => $token,
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
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'লগআউট সফল']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'সব ডিভাইস থেকে লগআউট সফল']);
    }

    private function ensureMemberRole(User $user): void
    {
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $user->assignRole($memberRole);
    }

    private function storeAvatar(User $user, Request $request): void
    {
        $user->addMediaFromRequest('image')->toMediaCollection('avatar');
        $user->forceFill(['avatar' => null])->save();
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
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
