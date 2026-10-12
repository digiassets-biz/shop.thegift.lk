<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;

class WebhookLogController extends Controller
{
    public function index()
    {
        return view('webhook-logs.index', [
            'logs' => WebhookLog::orderByDesc('created_at')->get(),
        ]);
    }
}
