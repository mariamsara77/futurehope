<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProfileController extends Controller
{
    public function pending(): JsonResponse
    {
        $profiles = Profile::with(['user:id,name,email,avatar', 'designation:id,name,order'])
            ->where('status', 'pending')
            ->latest('updated_at')
            ->get()
            ->map(fn (Profile $profile) => $this->format($profile));

        return response()->json(['profiles' => $profiles]);
    }

    public function approve(Request $request, Profile $profile): JsonResponse
    {
        $data = $request->validate([
            'designation_id' => ['required', 'exists:designations,id'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $profile->update([
            'designation_id' => $data['designation_id'],
            'priority' => $data['priority'],
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Profile approved successfully.',
            'profile' => $this->format($profile->fresh(['user', 'designation'])),
        ]);
    }

    public function reject(Request $request, Profile $profile): JsonResponse
    {
        $profile->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Profile rejected.',
            'profile' => $this->format($profile->fresh(['user', 'designation'])),
        ]);
    }

    private function format(Profile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'name' => $profile->user?->name,
            'email' => $profile->user?->email,
            'status' => $profile->status,
            'priority' => $profile->priority,
            'designation' => $profile->designation?->name,
            'designation_id' => $profile->designation_id,
            'phone' => $profile->phone,
            'education' => $profile->education,
            'blood_group' => $profile->blood_group,
            'bio' => $profile->bio,
            'avatar_url' => $profile->user?->avatar_url,
            'updated_at' => $profile->updated_at,
        ];
    }
}
