<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'uuid', 'order_reference', 'status', 'amount', 'currency',
        'merchant_validation_code', 'security_check_valid', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
        'security_check_valid' => 'boolean',
    ];
}
