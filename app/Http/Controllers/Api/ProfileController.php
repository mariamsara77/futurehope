<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        return response()->json([
            'profile' => $this->formatProfile($profile, $user),
        ]);
    }

    /**
     * Update or create authenticated user's profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'phone'             => ['nullable', 'string', 'max:20'],
            'father_name'       => ['nullable', 'string', 'max:100'],
            'mother_name'       => ['nullable', 'string', 'max:100'],
            'present_address'   => ['nullable', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'education'         => ['nullable', 'string', 'max:150'],
            'blood_group'       => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'image'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB
        ]);

        // Update or create profile
        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            collect($validated)->except('image')->toArray()
        );

        // Handle avatar upload
        if ($request->hasFile('image')) {
            try {
                $profile->clearMediaCollection('avatar');

                $profile->addMediaFromRequest('image')
                        ->usingFileName($request->file('image')->getClientOriginalName())
                        ->toMediaCollection('avatar');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Profile data saved, but image upload failed.',
                    'error'   => $e->getMessage(),
                    'profile' => $this->formatProfile($profile->fresh(), $user),
                ], 422);
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $this->formatProfile($profile->fresh(), $user),
        ]);
    }

    /**
     * Delete only the avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->hasMedia('avatar')) {
            return response()->json([
                'message' => 'No avatar found.',
            ], 404);
        }

        $profile->clearMediaCollection('avatar');

        return response()->json([
            'message' => 'Avatar deleted successfully',
            'profile' => $this->formatProfile($profile->fresh(), $user),
        ]);
    }

    /**
     * Format profile response
     */
    private function formatProfile(?Profile $profile, $user): array
    {
        if (!$profile) {
            return [
                'user_id'            => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => null,
                'father_name'        => null,
                'mother_name'        => null,
                'present_address'    => null,
                'permanent_address'  => null,
                'education'          => null,
                'blood_group'        => null,
                'bio'                => null,
                'avatar_url'         => null,
            ];
        }

        return [
            'id'                 => $profile->id,
            'user_id'            => $profile->user_id,
            'name'               => $user->name,
            'email'              => $user->email,
            'phone'              => $profile->phone,
            'father_name'        => $profile->father_name,
            'mother_name'        => $profile->mother_name,
            'present_address'    => $profile->present_address,
            'permanent_address'  => $profile->permanent_address,
            'education'          => $profile->education,
            'blood_group'        => $profile->blood_group,
            'bio'                => $profile->bio,
            'avatar_url'         => $profile->getFirstMediaUrl('avatar') ?: null,
            'created_at'         => $profile->created_at,
            'updated_at'         => $profile->updated_at,
        ];
    }
}