<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $request->search . '%')
                                                    ->orWhere('email', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->period) {
            $days = match($request->period) {
                '7d'  => 7,
                '30d' => 30,
                '90d' => 90,
                default => 30,
            };
            $query->where('created_at', '>=', now()->subDays($days));
        }

        if ($request->action && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20);

        $stats = [
            'total'        => AuditLog::count(),
            'last_24h'     => AuditLog::where('created_at', '>=', now()->subDay())->count(),
            'active_users' => AuditLog::where('created_at', '>=', now()->subDay())->distinct('user_id')->count('user_id'),
            'most_common'  => AuditLog::selectRaw('action, count(*) as total')
                                ->groupBy('action')
                                ->orderByDesc('total')
                                ->value('action'),
        ];

        return response()->json([
            'logs'  => $logs,
            'stats' => $stats,
        ]);
    }

    public function export(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->period) {
            $days = match($request->period) {
                '7d'  => 7,
                '30d' => 30,
                '90d' => 90,
                default => 30,
            };
            $query->where('created_at', '>=', now()->subDays($days));
        }

        if ($request->action && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        $logs = $query->orderByDesc('created_at')->get();

        $csv  = "Data/Hora,Usuário,Email,Ação,Descrição,IP\n";
        foreach ($logs as $log) {
            $csv .= implode(',', [
                '"' . $log->created_at->format('d/m/Y H:i:s') . '"',
                '"' . ($log->user?->name  ?? 'Sistema') . '"',
                '"' . ($log->user?->email ?? '-') . '"',
                '"' . $log->action . '"',
                '"' . str_replace('"', '""', $log->description ?? '') . '"',
                '"' . ($log->ip_address ?? '-') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs.csv"',
        ]);
    }
}