<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, AuditLogService $auditLogService): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('comment', $ticket);

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        $auditLogService->record($request, 'comment added', $comment, null, $comment->only([
            'ticket_id',
            'user_id',
            'body',
        ]));

        $route = match (true) {
            $user->isAdmin() => 'admin.tickets.show',
            $user->isSupportAgent() => 'agent.tickets.show',
            default => 'tickets.show',
        };

        return redirect()
            ->route($route, $ticket)
            ->with('status', 'Comment added.');
    }
}
