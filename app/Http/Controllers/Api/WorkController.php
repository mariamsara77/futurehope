<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:suggested,voting,approved,running,upcoming,completed,rejected'],
            'category_id' => ['nullable', 'integer', 'exists:work_categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Work::with(['user:id,name', 'category:id,name', 'media']);

        if ($this->isApprovedMember($request->user())) {
            $query->withExists([
                'votes as has_voted' => fn ($query) => $query->where('user_id', $request->user()->id),
            ]);
        }

        $query
            // Publication is driven by the vote threshold, not by the status field.
            // Unpublished works remain visible here so approved members can vote.
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . trim($search) . '%')
                        ->orWhere('description', 'like', '%' . trim($search) . '%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->latest();

        $works = $query->paginate($validated['per_page'] ?? 12)->withQueryString();

        return response()->json([
            'works' => collect($works->items())
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
            'meta' => [
                'current_page' => $works->currentPage(),
                'last_page' => $works->lastPage(),
                'per_page' => $works->perPage(),
                'total' => $works->total(),
            ],
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->isApprovedMember($user)) {
            return response()->json([
                'message' => 'আপনার profile এখনো member approval পায়নি।',
            ], 403);
        }

        $userId = (int) $user->id;

        $works = Work::with(['user:id,name', 'category:id,name', 'media'])
            ->withExists([
                'votes as has_voted' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->where('is_published', false)
            ->latest()
            ->get();

        return response()->json([
            'works' => $works
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
        ]);
    }

    public function show(Work $work): JsonResponse
    {
        if (!$work->is_published && $work->votes_count >= $work->required_votes) {
            $work->checkAutoApprove();
            $work->refresh();
        }

        if (!$work->is_published && $work->status === 'rejected') {
            return response()->json(['message' => 'কাজটি পাওয়া যায়নি।'], 404);
        }

        $work->load(['user:id,name', 'category:id,name', 'updates.user:id,name', 'media']);

        return response()->json([
            'work' => $this->format($work, true),
        ]);
    }

    public function view(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        $canReview = $this->isApprovedMember($user);

        if (
            !$work->is_published &&
            (int) $work->user_id !== (int) $user->id &&
            !$canReview
        ) {
            return response()->json(['message' => 'কাজটি পাওয়া যায়নি।'], 404);
        }

        $work->load(['user:id,name', 'category:id,name', 'updates.user:id,name', 'media']);

        if ($canReview) {
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
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
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

        foreach ($request->file('images', []) as $image) {
            $work->addMedia($image)->toMediaCollection('gallery');
        }

        // Backward-compatible single-image field.
        if ($request->hasFile('image') && count($request->file('images', [])) === 0) {
            $work->addMediaFromRequest('image')->toMediaCollection('gallery');
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

        if (!$this->isApprovedMember($user)) {
            return response()->json([
                'message' => 'ভোট দেওয়ার জন্য approved member profile প্রয়োজন।',
            ], 403);
        }

        $result = DB::transaction(function () use ($user, $work): array {
            $lockedWork = Work::query()
                ->whereKey($work->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWork->is_published) {
                return [
                    'ok' => false,
                    'message' => 'কাজটি প্রয়োজনীয় ভোট পেয়ে ইতিমধ্যে প্রকাশিত হয়েছে।',
                    'status' => 422,
                ];
            }

            if (WorkVote::query()
                ->where('work_id', $lockedWork->id)
                ->where('user_id', $user->id)
                ->exists()) {
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

            return ['ok' => true, 'work' => $lockedWork];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        /** @var Work $updatedWork */
        $updatedWork = $result['work'];

        return response()->json([
            'message' => $updatedWork->is_published
                ? 'প্রয়োজনীয় ভোট পূর্ণ হয়েছে—কাজটি প্রকাশিত হয়েছে।'
                : 'ভোট সফল হয়েছে।',
            'votes_count' => $updatedWork->votes_count,
            'required' => $updatedWork->required_votes,
            'status' => $updatedWork->status,
            'approved' => (bool) $updatedWork->is_published,
            'is_published' => (bool) $updatedWork->is_published,
            'has_voted' => true,
        ]);
    }

    public function undoVote(Request $request, Work $work): JsonResponse
    {
        $user = $request->user();

        if (!$this->isApprovedMember($user)) {
            return response()->json([
                'message' => 'ভোট বাতিল করার জন্য approved member profile প্রয়োজন।',
            ], 403);
        }

        $result = DB::transaction(function () use ($user, $work): array {
            $lockedWork = Work::query()
                ->whereKey($work->id)
                ->lockForUpdate()
                ->firstOrFail();

            $vote = WorkVote::query()
                ->where('work_id', $lockedWork->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$vote) {
                return [
                    'ok' => false,
                    'message' => 'এই কাজে আপনার কোনো ভোট নেই।',
                    'status' => 422,
                ];
            }

            $vote->delete();
            $lockedWork->refresh();

            if ($lockedWork->votes_count < $lockedWork->required_votes) {
                $lockedWork->forceFill(['is_published' => false, 'status' => 'voting'])->save();
            }

            $lockedWork->refresh();

            return ['ok' => true, 'work' => $lockedWork];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        /** @var Work $updatedWork */
        $updatedWork = $result['work'];

        return response()->json([
            'message' => $updatedWork->is_published
                ? 'আপনার ভোট বাতিল করা হয়েছে।'
                : 'আপনার ভোট বাতিল করা হয়েছে এবং কাজটি আবার ভোটিংয়ে ফিরে গেছে।',
            'votes_count' => $updatedWork->votes_count,
            'required' => $updatedWork->required_votes,
            'status' => $updatedWork->status,
            'approved' => (bool) $updatedWork->is_published,
            'is_published' => (bool) $updatedWork->is_published,
            'has_voted' => false,
        ]);
    }

    public function myWorks(Request $request): JsonResponse
    {
        $works = Work::with(['category:id,name', 'media'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'works' => $works
                ->map(fn (Work $work) => $this->format($work))
                ->values(),
        ]);
    }

    private function isApprovedMember(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $user->loadMissing('profile');

        return $user->profile?->status === 'active';
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
            'images' => $work->gallery_urls,
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
            $formatted['updated_at'] = $work->updated_at?->toDateTimeString();
            $formatted['updates'] = $work->updates
                ->sortByDesc('created_at')
                ->values()
                ->map(fn ($update) => [
                    'id' => $update->id,
                    'title' => $update->title,
                    'description' => $update->description,
                    'author' => $update->user?->name,
                    'created_at' => $update->created_at?->toDateTimeString(),
                ]);
        }

        return $formatted;
    }
}