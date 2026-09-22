<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'works' => $query->get()
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $works = Work::with(['user:id,name', 'category:id,name'])
            ->withExists([
                'votes as has_voted' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->whereIn('status', ['suggested', 'voting'])
            ->where('is_published', false)
            ->latest()
            ->get();

        return response()->json([
            'works' => $works
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
        ]);
    }

    /**
     * Public detail endpoint. Only published works are public.
     */
    public function show(Work $work): JsonResponse
    {
        if (!$work->is_published) {
            return response()->json(['message' => 'কাজটি পাওয়া যায়নি।'], 404);
        }

        $work->load(['user:id,name', 'category:id,name']);

        return response()->json([
            'work' => $this->format($work, true),
        ]);
    }

    /**
     * Authenticated detail endpoint for work owners and voting members.
     */
    public function view(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        if (
            !$work->is_published &&
            $work->user_id !== $user->id &&
            !$user->can('work-vote')
        ) {
            return response()->json(['message' => 'কাজটি পাওয়া যায়নি।'], 404);
        }

        $work->load(['user:id,name', 'category:id,name']);

        if ($user->can('work-vote')) {
            $work->setAttribute(
                'has_voted',
                $work->votes()->where('user_id', $user->id)->exists()
            );
        }

        return response()->json([
            'work' => $this->format($work, true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                Rule::exists('work_categories', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)),
            ],
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
            'title' => trim($validated['title']),
            'description' => trim($validated['description']),
            'status' => 'voting',
            'is_published' => false,
            'votes_count' => 0,
            'required_votes' => 10,
        ]);

        if ($request->hasFile('image')) {
            $work->addMediaFromRequest('image')->toMediaCollection('cover');
        }

        $work->load(['user:id,name', 'category:id,name']);

        return response()->json([
            'message' => 'আপনার কাজের প্রস্তাব জমা হয়েছে। সদস্যদের ভোট শুরু হয়েছে।',
            'work' => $this->format($work, true),
        ], 201);
    }

    public function vote(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($user, $work): array {
            $lockedWork = Work::query()
                ->whereKey($work->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWork->is_published || $lockedWork->status !== 'voting') {
                return [
                    'ok' => false,
                    'message' => 'এই কাজে এখন ভোট দেওয়া যাচ্ছে না।',
                    'status' => 422,
                ];
            }

            if (
                WorkVote::query()
                    ->where('work_id', $lockedWork->id)
                    ->where('user_id', $user->id)
                    ->exists()
            ) {
                return [
                    'ok' => false,
                    'message' => 'আপনি ইতিমধ্যে এই কাজে ভোট দিয়েছেন।',
                    'status' => 422,
                ];
            }

            WorkVote::create([
                'work_id' => $lockedWork->id,
                'user_id' => $user->id,
            ]);

            $lockedWork->refresh();

            return [
                'ok' => true,
                'work' => $lockedWork,
            ];
        });

        if (!$result['ok']) {
            return response()->json(
                ['message' => $result['message']],
                $result['status']
            );
        }

        /** @var Work $updatedWork */
        $updatedWork = $result['work'];

        return response()->json([
            'message' => $updatedWork->status === 'approved'
                ? '১০টি ভোট পূর্ণ হয়েছে—কাজটি অনুমোদিত হয়েছে।'
                : 'ভোট সফল হয়েছে।',
            'votes_count' => $updatedWork->votes_count,
            'required' => $updatedWork->required_votes,
            'status' => $updatedWork->status,
            'approved' => $updatedWork->status === 'approved',
            'has_voted' => true,
        ]);
    }

    public function myWorks(Request $request): JsonResponse
    {
        $works = Work::with('category:id,name')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'works' => $works
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
        ]);
    }

    private function format(Work $work, bool $detailed = false): array
    {
        $formatted = [
            'id' => $work->id,
            'title' => $work->title,
            'description' => $work->description,
            'status' => $work->status,
            'is_published' => (bool) $work->is_published,
            'votes_count' => (int) $work->votes_count,
            'required_votes' => (int) $work->required_votes,
            'vote_progress' => min(
                100,
                (int) round(
                    ($work->votes_count / max(1, $work->required_votes)) * 100
                )
            ),
            'cover_url' => $work->cover_url,
            'category' => $work->category?->name,
            'submitted_name' => $work->submitted_name,
            'created_at' => $work->created_at?->toDateTimeString(),
            'user' => [
                'id' => $work->user?->id,
                'name' => $work->user?->name,
            ],
        ];

        if ($work->getAttribute('has_voted') !== null) {
            $formatted['has_voted'] = (bool) $work->getAttribute('has_voted');
        }

        if ($detailed) {
            $formatted['submitted_email'] = $work->submitted_email;
            $formatted['updated_at'] = $work->updated_at?->toDateTimeString();
        }

        return $formatted;
    }
}
