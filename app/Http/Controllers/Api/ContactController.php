<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create([
            ...$data,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $recipient = config('mail.contact_to');

            if (!$recipient || (app()->isProduction() && config('mail.default') === 'log')) {
                throw new \RuntimeException('Production mail delivery is not configured.');
            }

            Mail::to($recipient)->send(new ContactMessageMail($contactMessage));

            $contactMessage->forceFill([
                'mail_status' => 'sent',
                'mail_error' => null,
            ])->save();

            return response()->json([
                'message' => 'আপনার বার্তা সফলভাবে পাঠানো হয়েছে।',
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            $contactMessage->forceFill([
                'mail_status' => 'failed',
                'mail_error' => $e->getMessage(),
            ])->save();

            return response()->json([
                'message' => 'বার্তাটি সংরক্ষণ হয়েছে, কিন্তু ইমেইল পাঠানো যায়নি। কিছুক্ষণ পরে আবার চেষ্টা করুন।',
            ], 503);
        }
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('contact-manage'), 403);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,sent,failed'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $messages = ContactMessage::query()
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('mail_status', $status))
            ->when($validated['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($messages);
    }

    public function show(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        abort_unless($request->user()?->can('contact-manage'), 403);

        return response()->json(['message' => $contactMessage]);
    }

}