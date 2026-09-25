<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {
        // Spam protection
        $key = 'contact-form:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'অনেকবার চেষ্টা করেছেন। কিছুক্ষণ পর আবার চেষ্টা করুন।',
            ], 429);
        }
        RateLimiter::hit($key, 60); // ১ মিনিট

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:150',
            'subject' => 'nullable|string|min:3|max:150',
            'message' => 'required|string|min:10|max:2000',
            'phone' => 'nullable|string|max:20',
            'priority' => 'nullable|in:normal,urgent',
            'category' => 'nullable|string|max:50',
        ], [
            'name.required' => 'নাম আবশ্যক',
            'email.required' => 'ইমেইল আবশ্যক',
            'email.email' => 'সঠিক ইমেইল দিন',
            'message.required' => 'মেসেজ আবশ্যক',
            'message.min' => 'মেসেজ কমপক্ষে ১০ অক্ষরের হতে হবে',
        ]);

        try {
            Mail::to('admin@totthobox.com')
                ->send(new ContactMail(
                    name: $validated['name'],
                    email: $validated['email'],
                    message: $validated['message'],
                    mailSubject: $validated['subject'] ?? null,   // ← এখানে mailSubject
                    phone: $validated['phone'] ?? null,
                    priority: $validated['priority'] ?? 'normal',
                    category: $validated['category'] ?? null,
                ));

            return response()->json([
                'success' => true,
                'message' => 'আপনার মেসেজ সফলভাবে পাঠানো হয়েছে। আমরা শীঘ্রই উত্তর দিব।',
            ]);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'মেসেজ পাঠাতে সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।',
            ], 500);
        }
    }
}
