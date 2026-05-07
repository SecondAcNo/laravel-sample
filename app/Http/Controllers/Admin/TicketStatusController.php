<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Ticket;
use App\Services\AuditLogService;
use App\Services\TicketStatusService;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function update(
        UpdateTicketStatusRequest $request,
        Ticket $ticket,
        TicketStatusService $ticketStatusService,
        AuditLogService $auditLogService
    ): RedirectResponse {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorize('updateStatus', $ticket);

        $beforeValues = $ticket->only(['status', 'closed_at']);
        $ticketStatusService->transition($ticket, $request->user(), $request->validated('status'));

        $auditLogService->record($request, 'status changed', $ticket, $beforeValues, $ticket->only([
            'status',
            'closed_at',
        ]));

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('status', 'Ticket status updated.');
    }
}
