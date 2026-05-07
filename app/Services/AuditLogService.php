<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * @param array<string, mixed>|null $beforeValues
     * @param array<string, mixed>|null $afterValues
     */
    public function record(
        Request $request,
        string $action,
        Model $target,
        ?array $beforeValues = null,
        ?array $afterValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
