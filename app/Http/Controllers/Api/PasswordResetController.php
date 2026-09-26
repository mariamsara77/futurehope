<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $status = Password::sendResetLink([
            'email' => strtolower(trim($data['email'])),
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'যদি এই ইমেইল দিয়ে একটি অ্যাকাউন্ট থাকে, তাহলে পাসওয়ার্ড রিসেট করার লিংক পাঠানো হয়েছে।',
            ]);
        }

        // Keep the response intentionally generic so account existence is not exposed.
        return response()->json([
            'message' => 'যদি এই ইমেইল দিয়ে একটি অ্যাকাউন্ট থাকে, তাহলে পাসওয়ার্ড রিসেট করার লিংক পাঠানো হয়েছে।',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'password.confirmed' => 'পাসওয়ার্ড দুটো একই হতে হবে।',
        ]);

        $status = Password::reset(
            [
                'token' => $data['token'],
                'email' => strtolower(trim($data['email'])),
                'password' => $data['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function ($user) use ($data) {
                $user->forceFill([
                    'password' => Hash::make($data['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                // A password reset invalidates existing API sessions on all devices.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'পাসওয়ার্ড সফলভাবে রিসেট হয়েছে। এখন নতুন পাসওয়ার্ড দিয়ে লগইন করুন।',
            ]);
        }

        throw ValidationException::withMessages([
            'token' => ['এই পাসওয়ার্ড রিসেট লিংকটি সঠিক নয় বা এর মেয়াদ শেষ হয়েছে। নতুন রিসেট লিংক নিন।'],
        ]);
    }
}
