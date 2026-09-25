<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $otp) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার ওটিপি (OTP) ভেরিফিকেশন কোড',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlLayout(),
        );
    }

    /**
     * প্রফেশনাল ইমেইল লেআউট তৈরি
     */
    protected function buildHtmlLayout(): string
    {
        return "
        <div style='font-family: sans-serif; background-color: #f4f4f7; padding: 40px 20px; color: #333;'>
            <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;'>
                
                <h2 style='color: #1a1a1a; margin-bottom: 10px;'>ইমেইল যাচাই করুন</h2>
                <p style='font-size: 15px; color: #666; line-height: 1.5;'>আমাদের প্ল্যাটফর্মে রেজিস্ট্রেশন সম্পন্ন করার জন্য নিচের ৪ ডিজিটের ভেরিফিকেশন কোডটি ব্যবহার করুন।</p>
                
                <div style='background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                    <span style='font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #2563eb;'>{$this->otp}</span>
                </div>
                
                <p style='font-size: 13px; color: #94a3b8;'>এই কোডটির মেয়াদ থাকবে মাত্র <b>১০ মিনিট</b>।</p>
                
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                
                <p style='font-size: 12px; color: #94a3b8; line-height: 1.4;'>
                    আপনি যদি এই অনুরোধ না করে থাকেন, তবে নির্দ্বিধায় এই ইমেইলটি উপেক্ষা করুন।<br>
                    ধন্যবাদ, <b>".config('app.name').'</b> টিম।
                </p>
            </div>
        </div>
        ';
    }

    public function attachments(): array
    {
        return [];
    }
}
