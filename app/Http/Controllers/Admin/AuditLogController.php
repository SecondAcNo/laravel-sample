<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->only(['q', 'action', 'target_type']);

        return view('admin.audit-logs.index', [
            'auditLogs' => AuditLog::query()
                ->with('user')
                ->filter($filters)
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'targetTypes' => AuditLog::query()
                ->select('target_type')
                ->distinct()
                ->orderBy('target_type')
                ->pluck('target_type'),
        ]);
    }
}
