<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkController extends Controller
{
    // Public: Website এ দেখানোর জন্য
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status'); // running, upcoming, approved, completed...

        $query = Work::with(['user:id,name', 'category:id,name'])
            ->where('is_published', true)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $works = $query->get()->map(fn ($work) => $this->format($work));

        return response()->json(['works' => $works]);
    }

    // Public: Single work
    public function show(Work $work): JsonResponse
    {
        if (!$work->is_published && auth()->id() !== $work->user_id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $work->load(['user:id,name', 'category:id,name']);

        return response()->json(['work' => $this->format($work, true)]);
    }

    // Protected: User suggestion create
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:work_categories,id'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ]);

        $work = Work::create([
            'user_id'     => $request->user()->id,
            'category_id' => $validated['category_id'] ?? null,
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status'      => 'voting',
        ]);

        if ($request->hasFile('image')) {
            $work->addMediaFromRequest('image')->toMediaCollection('cover');
        }

        return response()->json([
            'message' => 'আপনার সাজেশন জমা হয়েছে',
            'work'    => $this->format($work->fresh()),
        ], 201);
    }

    // Protected: Vote
    public function vote(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        if ($work->hasUserVoted($user->id)) {
            return response()->json(['message' => 'আপনি ইতিমধ্যে ভোট দিয়েছেন'], 422);
        }

        if (!in_array($work->status, ['voting', 'suggested'])) {
            return response()->json(['message' => 'এই কাজে আর ভোট দেওয়া যাবে না'], 422);
        }

        WorkVote::create([
            'work_id' => $work->id,
            'user_id' => $user->id,
        ]);

        $work->refresh();

        return response()->json([
            'message'     => 'ভোট সফল',
            'votes_count' => $work->votes_count,
            'required'    => $work->required_votes,
            'status'      => $work->status,
            'approved'    => $work->status === 'approved',
        ]);
    }

    // Protected: My suggestions
    public function myWorks(Request $request): JsonResponse
    {
        $works = Work::with('category:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($work) => $this->format($work));

        return response()->json(['works' => $works]);
    }

    private function format(Work $work, bool $detailed = false): array
    {
        return [
            'id'          => $work->id,
            'title'       => $work->title,
            'description' => $work->description,
            'status'      => $work->status,
            'is_published'=> $work->is_published,
            'votes_count' => $work->votes_count,
            'required_votes' => $work->required_votes,
            'cover_url'   => $work->cover_url,
            'category'    => $work->category?->name,
            'user'        => [
                'id'   => $work->user?->id,
                'name' => $work->user?->name,
            ],
            'created_at'  => $work->created_at?->toDateTimeString(),
        ];
    }
}