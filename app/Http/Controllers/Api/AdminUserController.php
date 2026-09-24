<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::with(['profile.designation', 'roles'])
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $term = trim($search);
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->latest()
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'users' => $users->getCollection()->map(fn (User $user) => $this->formatUser($user))->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['profile.designation', 'roles', 'permissions']);

        return response()->json(['user' => $this->formatUser($user, true)]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'status' => ['required', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:20'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'present_address' => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'education' => ['nullable', 'string', 'max:150'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'bio' => ['nullable', 'string', 'max:1000'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'profile_status' => ['nullable', 'in:pending,active,rejected'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ((int) $request->user()->id === (int) $user->id && isset($data['roles']) && !in_array('admin', $data['roles'], true)) {
            return response()->json(['message' => 'নিজের admin role নিজে সরানো যাবে না।'], 422);
        }

        $user->forceFill([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'status' => $data['status'],
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('roles', $data)) {
            $roles = $data['roles'] ?: ['member'];
            $user->syncRoles($roles);
        }

        $profileData = [
            'phone' => $data['phone'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'mother_name' => $data['mother_name'] ?? null,
            'present_address' => $data['present_address'] ?? null,
            'permanent_address' => $data['permanent_address'] ?? null,
            'education' => $data['education'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'bio' => $data['bio'] ?? null,
            'designation_id' => $data['designation_id'] ?? null,
            'priority' => $data['priority'] ?? 999,
        ];

        if (array_key_exists('profile_status', $data)) {
            $profileData['status'] = $data['profile_status'];
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        if ($request->hasFile('image')) {
            $user->addMediaFromRequest('image')->toMediaCollection('avatar');
            $user->forceFill(['avatar' => null])->save();
        }

        $user->refresh()->load(['profile.designation', 'roles', 'permissions']);

        return response()->json([
            'message' => 'User ও profile তথ্য সফলভাবে আপডেট হয়েছে।',
            'user' => $this->formatUser($user, true),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json(['message' => 'নিজের account নিজে delete করা যাবে না।'], 422);
        }

        $user->tokens()->delete();
        $user->clearMediaCollection('avatar');
        $user->delete();

        return response()->json(['message' => 'User সফলভাবে মুছে ফেলা হয়েছে।']);
    }

    private function formatUser(User $user, bool $detailed = false): array
    {
        $user->loadMissing(['profile.designation', 'roles']);

        $profile = $user->profile;

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'avatar' => $user->avatar_url,
            'roles' => $user->getRoleNames()->values()->all(),
            'is_member' => $user->hasRole('admin') || $profile?->status === 'active',
            'profile_status' => $profile?->status,
            'designation' => $profile?->designation?->name,
            'designation_id' => $profile?->designation_id,
            'priority' => $profile?->priority ?? 999,
        ];

        if ($detailed) {
            $data['permissions'] = $user->getAllPermissions()->pluck('name')->values()->all();
            $data['profile'] = [
                'phone' => $profile?->phone,
                'father_name' => $profile?->father_name,
                'mother_name' => $profile?->mother_name,
                'present_address' => $profile?->present_address,
                'permanent_address' => $profile?->permanent_address,
                'education' => $profile?->education,
                'blood_group' => $profile?->blood_group,
                'bio' => $profile?->bio,
            ];
            $data['created_at'] = $user->created_at;
            $data['updated_at'] = $user->updated_at;
        }

        return $data;
    }
}
