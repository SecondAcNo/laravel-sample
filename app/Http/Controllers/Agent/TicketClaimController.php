<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketClaimController extends Controller
{
    public function update(Request $request, Ticket $ticket, AuditLogService $auditLogService): RedirectResponse
    {
        abort_unless($request->user()->isSupportAgent(), 403);
        $this->authorize('claim', $ticket);

        $beforeValues = [
            'assignee_id' => $ticket->assignee_id,
        ];

        $ticket->update([
            'assignee_id' => $request->user()->id,
        ]);

        $auditLogService->record(
            $request,
            'ticket assignee changed',
            $ticket,
            $beforeValues,
            ['assignee_id' => $ticket->assignee_id],
        );

        return redirect()
            ->route('agent.tickets.show', $ticket)
            ->with('status', 'Ticket claimed.');
    }
}
