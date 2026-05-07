<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTicketRequest;
use App\Models\Ticket;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function update(
        AssignTicketRequest $request,
        Ticket $ticket,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $this->authorize('assign', $ticket);

        $beforeValues = [
            'assignee_id' => $ticket->assignee_id,
        ];
        $assigneeId = $request->validated('assignee_id') ?: null;

        $ticket->update([
            'assignee_id' => $assigneeId,
        ]);

        $auditLogService->record(
            $request,
            'ticket assignee changed',
            $ticket,
            $beforeValues,
            ['assignee_id' => $ticket->assignee_id],
        );

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('status', 'Ticket assignee updated.');
    }
}
