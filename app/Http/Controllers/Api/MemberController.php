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
            ->get()
            ->sortBy(fn ($p) => $p->designation?->order ?? 999)
            ->values()
            ->map(function ($profile) {
                return [
                    'id'          => $profile->id,
                    'name'        => $profile->user->name,
                    'designation' => $profile->designation?->name,
                    'bio'         => $profile->bio,
                    'avatar_url'  => $profile->getFirstMediaUrl('avatar') ?: null,
                    'blood_group' => $profile->blood_group,
                ];
            });

        return response()->json(['members' => $members]);
    }
}