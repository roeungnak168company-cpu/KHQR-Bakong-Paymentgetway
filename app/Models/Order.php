<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'md5',
        'invoice_id',
        'khqr_string',
        'items_json',
        'total_khr',
        'currency',
        'status',
        'expires_at',
        'paid_at',
        'paid_data_json',
        'telegram_notified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'telegram_notified_at' => 'datetime',
    ];
}
