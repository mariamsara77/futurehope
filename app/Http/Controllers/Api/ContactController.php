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
}