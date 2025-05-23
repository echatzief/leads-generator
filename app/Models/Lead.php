<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'allow_send_emails',
    ];

    protected $casts = [
        'allow_send_emails' => 'boolean',
    ];
}
