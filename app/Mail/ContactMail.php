<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $message,
        public ?string $mailSubject = null,   // ← নাম পরিবর্তন করা হয়েছে
        public ?string $phone = null,
        public ?string $priority = null,
        public ?string $category = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->mailSubject ?? 'নতুন যোগাযোগ - '.config('app.name');

        if ($this->priority === 'urgent') {
            $subject = '[URGENT] '.$subject;
        }

        return new Envelope(
            subject: $subject,
            replyTo: $this->email
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'messageContent' => $this->message,
                'phone' => $this->phone,
                'priority' => $this->priority,
                'category' => $this->category,
                'subject' => $this->mailSubject,  // ভিউতে পাঠানোর জন্য
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
