<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Work::with(['user:id,name', 'category:id,name'])
            ->where('is_published', true)
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json([
            'works' => $query->get()->map(fn (Work $work) => $this->format($work))->values(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $works = Work::with(['user:id,name', 'category:id,name'])
            ->whereIn('status', ['suggested', 'voting'])
            ->where('is_published', false)
            ->latest()
            ->get();

        return response()->json([
            'works' => $works->map(fn (Work $work) => $this->format($work))->values(),
        ]);
    }

    public function show(Work $work): JsonResponse
    {
        if (!$work->is_published) {
            $user = request()->user();
            if (!$user || $user->id !== $work->user_id) {
                return response()->json(['message' => 'Not found'], 404);
            }
        }

        $work->load(['user:id,name', 'category:id,name']);
        return response()->json(['work' => $this->format($work, true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:work_categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'submitted_name' => ['nullable', 'string', 'max:100'],
            'submitted_email' => ['nullable', 'email', 'max:255'],
        ]);

        $user = $request->user();

        $work = Work::create([
            'user_id' => $user?->id,
            'category_id' => $validated['category_id'] ?? null,
            'submitted_name' => $user?->name ?? ($validated['submitted_name'] ?? null),
            'submitted_email' => $user?->email ?? ($validated['submitted_email'] ?? null),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'voting',
            'required_votes' => 10,
        ]);

        if ($request->hasFile('image')) {
            $work->addMediaFromRequest('image')->toMediaCollection('cover');
        }

        return response()->json([
            'message' => 'আপনার কাজের প্রস্তাব জমা হয়েছে। সদস্যদের ভোট শুরু হয়েছে।',
            'work' => $this->format($work->fresh(['user', 'category'])),
        ], 201);
    }

    public function vote(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        if ($work->status !== 'voting') {
            return response()->json(['message' => 'এই কাজে এখন ভোট দেওয়া যাচ্ছে না।'], 422);
        }

        $created = DB::transaction(function () use ($user, $work) {
            $work = Work::whereKey($work->id)->lockForUpdate()->firstOrFail();

            if ($work->status !== 'voting') {
                return false;
            }

            if (WorkVote::where('work_id', $work->id)->where('user_id', $user->id)->exists()) {
                return false;
            }

            WorkVote::create(['work_id' => $work->id, 'user_id' => $user->id]);
            return true;
        });

        if (!$created) {
            return response()->json(['message' => 'আপনি ইতিমধ্যে ভোট দিয়েছেন অথবা ভোট বন্ধ হয়ে গেছে।'], 422);
        }

        $work->refresh();

        return response()->json([
            'message' => 'ভোট সফল।',
            'votes_count' => $work->votes_count,
            'required' => $work->required_votes,
            'status' => $work->status,
            'approved' => $work->status === 'approved',
        ]);
    }

    public function myWorks(Request $request): JsonResponse
    {
        $works = Work::with('category:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['works' => $works->map(fn (Work $work) => $this->format($work))->values()]);
    }

    private function format(Work $work, bool $detailed = false): array
    {
        return [
            'id' => $work->id,
            'title' => $work->title,
            'description' => $work->description,
            'status' => $work->status,
            'is_published' => $work->is_published,
            'votes_count' => $work->votes_count,
            'required_votes' => $work->required_votes,
            'vote_progress' => min(100, (int) round(($work->votes_count / max(1, $work->required_votes)) * 100)),
            'cover_url' => $work->cover_url,
            'category' => $work->category?->name,
            'submitted_name' => $work->submitted_name,
            'created_at' => $work->created_at?->toDateTimeString(),
            'user' => [
                'id' => $work->user?->id,
                'name' => $work->user?->name,
            ],
        ];
    }
}
