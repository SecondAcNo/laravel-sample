<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->with(['category', 'requester', 'assignee'])
            ->filter($request->only(['q', 'status', 'priority', 'type', 'category_id']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'priority', 'type', 'category_id']),
        ]);
    }

    public function show(Request $request, Ticket $ticket, TicketStatusService $ticketStatusService): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['category', 'requester', 'assignee', 'comments.user', 'attachments.uploader']);
        $supportAgents = User::query()
            ->where('role', 'support_agent')
            ->orderBy('name')
            ->get();

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'supportAgents' => $supportAgents,
            'availableStatuses' => $ticketStatusService->availableStatusesFor($ticket, $request->user()),
        ]);
    }
}
