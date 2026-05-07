<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function dashboard(Request $request): View
    {
        abort_unless($request->user()->isSupportAgent(), 403);

        $assignedTickets = Ticket::query()
            ->where('assignee_id', $request->user()->id);

        return view('agent.dashboard', [
            'openCount' => (clone $assignedTickets)->where('status', Ticket::STATUS_OPEN)->count(),
            'inProgressCount' => (clone $assignedTickets)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'urgentCount' => (clone $assignedTickets)->where('priority', Ticket::PRIORITY_URGENT)->count(),
            'unassignedCount' => Ticket::query()->whereNull('assignee_id')->count(),
            'recentTickets' => (clone $assignedTickets)
                ->with(['category', 'requester'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentUnassignedTickets' => Ticket::query()
                ->with(['category', 'requester'])
                ->whereNull('assignee_id')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isSupportAgent(), 403);

        $tickets = Ticket::query()
            ->with(['category', 'requester', 'assignee'])
            ->where(function ($query) use ($request) {
                $query
                    ->where('assignee_id', $request->user()->id)
                    ->orWhereNull('assignee_id');
            })
            ->when($request->string('assignee')->toString() === 'assigned', function ($query) use ($request) {
                $query->where('assignee_id', $request->user()->id);
            })
            ->when($request->string('assignee')->toString() === 'unassigned', function ($query) {
                $query->whereNull('assignee_id');
            })
            ->filter($request->only(['q', 'status', 'priority', 'type', 'category_id']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('agent.tickets.index', [
            'tickets' => $tickets,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'priority', 'type', 'category_id', 'assignee']),
        ]);
    }

    public function show(Request $request, Ticket $ticket, TicketStatusService $ticketStatusService): View
    {
        abort_unless($request->user()->isSupportAgent(), 403);
        $this->authorize('viewAssigned', $ticket);

        $ticket->load(['category', 'requester', 'assignee', 'comments.user', 'attachments.uploader']);

        return view('agent.tickets.show', [
            'ticket' => $ticket,
            'availableStatuses' => $ticketStatusService->availableStatusesFor($ticket, $request->user()),
        ]);
    }
}
