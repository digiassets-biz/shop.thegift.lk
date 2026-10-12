<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'uuid', 'order_reference', 'status', 'amount', 'currency',
        'merchant_validation_code', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];
}
