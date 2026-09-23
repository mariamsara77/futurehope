<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'mail_status',
        'mail_error',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'mail_error' => 'encrypted',
    ];
}