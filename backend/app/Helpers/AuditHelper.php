<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditHelper
{
    public static function log(string $action, string $description): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'description' => $description,
            'ip_address'  => Request::ip(),
        ]);
    }
}