<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profile.designation');

        return response()->json([
            'profile' => $this->formatProfile($user->profile, $user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'present_address' => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'education' => ['nullable', 'string', 'max:150'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'bio' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->forceFill(['name' => trim((string) $validated['name'])])->save();
        }

        $profileData = collect($validated)->except('image', 'name')->toArray();
        $existingStatus = $user->profile?->status;

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                ...$profileData,
                // Do not remove voting access from an already-approved member
                // merely because they edited their profile details.
                'status' => $existingStatus === 'active' ? 'active' : 'pending',
            ]
        );

        if ($request->hasFile('image')) {
            try {
                // Avatar is owned by User, not Profile.
                // singleFile() replaces the previous local image only after
                // the new media item is accepted.
                $user->addMediaFromRequest('image')->toMediaCollection('avatar');
                // Keep the Google avatar URL as fallback for a later delete.
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Profile saved, but image upload failed.',
                    'profile' => $this->formatProfile($profile->fresh('designation'), $user->fresh()),
                ], 422);
            }
        }

        $user->refresh()->load('profile.designation');

        return response()->json([
            'message' => 'Profile submitted for admin approval.',
            'profile' => $this->formatProfile($user->profile, $user),
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasMedia('avatar') && blank($user->avatar)) {
            return response()->json([
                'message' => 'কোনো profile image পাওয়া যায়নি।',
            ], 404);
        }

        $user->clearMediaCollection('avatar');
        $user->refresh()->load('profile.designation');

        return response()->json([
            'message' => 'Profile image removed. Google avatar থাকলে সেটি fallback হিসেবে থাকবে।',
            'profile' => $this->formatProfile($user->profile, $user),
        ]);
    }

    private function formatProfile(?Profile $profile, $user): array
    {
        return [
            'id' => $profile?->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $profile?->phone,
            'father_name' => $profile?->father_name,
            'mother_name' => $profile?->mother_name,
            'present_address' => $profile?->present_address,
            'permanent_address' => $profile?->permanent_address,
            'education' => $profile?->education,
            'blood_group' => $profile?->blood_group,
            'bio' => $profile?->bio,
            'status' => $profile?->status ?? 'pending',
            'priority' => $profile?->priority ?? 999,
            'designation' => $profile?->designation?->name,
            'designation_id' => $profile?->designation_id,
            'avatar_url' => $user->avatar_url,
            'has_uploaded_avatar' => $user->hasMedia('avatar'),
            'created_at' => $profile?->created_at,
            'updated_at' => $profile?->updated_at,
        ];
    }
}
