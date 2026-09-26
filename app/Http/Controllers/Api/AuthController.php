<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
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
        ], [
            'email.unique' => 'এই ইমেইল দিয়ে আগে থেকেই একটি অ্যাকাউন্ট আছে। লগইন করুন অথবা Google দিয়ে প্রবেশ করুন।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'password.confirmed' => 'পাসওয়ার্ড দুটো একই হতে হবে।',
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'status' => 'active',
        ]);

        $this->ensureMemberRole($user);
        $this->ensureMemberProfile($user);

        if ($request->hasFile('image')) {
            $this->storeAvatar($user, $request);
            $user->refresh();
        }

        $token = $this->issueToken($user, $request, 'frontend');

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

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['ইমেইল বা পাসওয়ার্ড মিলছে না। আবার চেষ্টা করুন।'],
            ]);
        }

        if (!$user->password) {
            throw ValidationException::withMessages([
                'email' => ['এই অ্যাকাউন্টটি Google দিয়ে তৈরি হয়েছে। Google দিয়ে লগইন করুন।'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ইমেইল বা পাসওয়ার্ড মিলছে না। আবার চেষ্টা করুন।'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'আপনার অ্যাকাউন্টটি বর্তমানে সক্রিয় নয়।',
            ], 403);
        }

        $this->ensureMemberRole($user);
        $this->ensureMemberProfile($user);

        $token = $this->issueToken($user, $request, 'frontend');

        return response()->json([
            'message' => 'লগইন সফল',
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureMemberRole($user);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'password.confirmed' => 'পাসওয়ার্ড দুটো একই হতে হবে।',
        ]);

        $user = $request->user();

        if (!$user || !$user->password || !Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['বর্তমান পাসওয়ার্ডটি সঠিক নয়।'],
            ]);
        }

        $user->forceFill(['password' => $data['password']])->save();

        $currentTokenId = $user->currentAccessToken()?->id;
        if ($currentTokenId) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে। অন্য ডিভাইসগুলোর পুরোনো সেশন বন্ধ করা হয়েছে।',
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

    private function issueToken(User $user, Request $request, string $fallbackName): string
    {
        $deviceName = trim((string) $request->userAgent());
        $deviceName = $deviceName !== '' ? $deviceName : $fallbackName;

        $user->tokens()->where('name', $deviceName)->delete();

        return $user->createToken($deviceName)->plainTextToken;
    }

    private function ensureMemberRole(User $user): void
    {
        $permission = Permission::firstOrCreate(['name' => 'work-vote']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        if (!$memberRole->hasPermissionTo($permission)) {
            $memberRole->givePermissionTo($permission);
        }

        if ($user->hasRole('admin')) {
            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }

            return;
        }

        $user->assignRole($memberRole);
    }

    private function ensureMemberProfile(User $user): void
    {
        $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active']
        );
    }

    private function storeAvatar(User $user, Request $request): void
    {
        $user->addMediaFromRequest('image')->toMediaCollection('avatar');
        $user->forceFill(['avatar' => null])->save();
    }

    private function formatUser(User $user): array
    {
        $user->loadMissing('profile');

        $isMember = $user->hasRole('admin') || $user->profile?->status === 'active';

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'avatar' => $user->avatar_url,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'is_member' => $isMember,
            'profile_status' => $user->profile?->status,
        ];
    }
}
