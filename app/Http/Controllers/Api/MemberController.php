<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Profile::with(['user:id,name', 'designation:id,name,order'])
            ->where('status', 'active')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn (Profile $profile) => [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'name' => $profile->user?->name,
                'designation' => $profile->designation?->name,
                'designation_order' => $profile->designation?->order,
                'priority' => $profile->priority,
                'bio' => $profile->bio,
                'avatar_url' => $profile->getFirstMediaUrl('avatar') ?: null,
                'blood_group' => $profile->blood_group,
            ]);

        return response()->json(['members' => $members]);
    }
}
